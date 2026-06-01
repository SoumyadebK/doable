<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$userType = "Customers";
$user_role_condition = " AND PK_ROLES = 4";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

$ALL_APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.*,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_MASTER.PK_SERVICE_MASTER,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            GROUP_CONCAT(SERVICE_PROVIDER.PK_USER SEPARATOR ',') AS SERVICE_PROVIDER_ID,
                            GROUP_CONCAT(DOA_USER_MASTER.PK_USER_MASTER SEPARATOR ',') AS CUSTOMER_ID
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                        
                        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = " . $_POST['PK_APPOINTMENT_MASTER'];

$res = $db_account->Execute($ALL_APPOINTMENT_QUERY);

if ($res->RecordCount() == 0) {
    header("location:all_services.php");
    exit;
}

$PK_APPOINTMENT_MASTER = $_POST['PK_APPOINTMENT_MASTER'];
$STANDING_ID = $res->fields['STANDING_ID'];
$CUSTOMER_ID = $res->fields['CUSTOMER_ID'];
$PK_ENROLLMENT_MASTER = $res->fields['PK_ENROLLMENT_MASTER'];
$PK_ENROLLMENT_SERVICE = $res->fields['PK_ENROLLMENT_SERVICE'];
$SERIAL_NUMBER = $res->fields['SERIAL_NUMBER'];
$PK_SERVICE_MASTER = $res->fields['PK_SERVICE_MASTER'];
$SERVICE_NAME = $res->fields['SERVICE_NAME'];
$PK_SERVICE_CODE = $res->fields['PK_SERVICE_CODE'];
$PK_SCHEDULING_CODE = $res->fields['PK_SCHEDULING_CODE'];
$SERVICE_PROVIDER_ID = $res->fields['SERVICE_PROVIDER_ID'];
$PK_APPOINTMENT_STATUS = $res->fields['PK_APPOINTMENT_STATUS'];
$NO_SHOW = $res->fields['NO_SHOW'];
$ACTIVE = $res->fields['ACTIVE'];
$DATE = date("m/d/Y", strtotime($res->fields['DATE']));
$DATE_ARR[0] = date("Y", strtotime($res->fields['DATE']));
$DATE_ARR[1] = date("m", strtotime($res->fields['DATE'])) - 1;
$DATE_ARR[2] = date("d", strtotime($res->fields['DATE']));
$START_TIME = $res->fields['START_TIME'];
$END_TIME = $res->fields['END_TIME'];
$COMMENT = $res->fields['COMMENT'];
$INTERNAL_COMMENT = $res->fields['INTERNAL_COMMENT'];
$IMAGE = $res->fields['IMAGE'];
$VIDEO = $res->fields['VIDEO'];
$IMAGE_2 = $res->fields['IMAGE_2'];
$VIDEO_2 = $res->fields['VIDEO_2'];
$IS_CHARGED = $res->fields['IS_CHARGED'];
$APPOINTMENT_TYPE = $res->fields['APPOINTMENT_TYPE'];

$status_data = $db_account->Execute("SELECT DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE AS STATUS_COLOR, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_APPOINTMENT_STATUS_HISTORY.TIME_STAMP FROM DOA_APPOINTMENT_STATUS_HISTORY LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS=DOA_APPOINTMENT_STATUS_HISTORY.PK_APPOINTMENT_STATUS LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER=DOA_APPOINTMENT_STATUS_HISTORY.PK_USER WHERE PK_APPOINTMENT_MASTER = '$_POST[PK_APPOINTMENT_MASTER]'");
$CHANGED_BY = '';
while (!$status_data->EOF) {
    $CHANGED_BY .= "(<span style='color: " . $status_data->fields['STATUS_COLOR'] . "'>" . $status_data->fields['APPOINTMENT_STATUS'] . "</span> by " . $status_data->fields['NAME'] . " at " . date('m-d-Y H:i:s A', strtotime($status_data->fields['TIME_STAMP'])) . ")<br>";
    $status_data->MoveNext();
}

$CREATE_LOGIN = 0;
$user_doc_count = 0;

if (empty($_GET['id']))
    $title = "Add " . $userType;
else
    $title = "Edit " . $userType;

if (!empty($_GET['tab']))
    $title = $userType;

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

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

$customer_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$CUSTOMER_ID'");

$selected_customer = $customer_data->fields['NAME'];
$customer_phone = $customer_data->fields['PHONE'];
$customer_email = $customer_data->fields['EMAIL_ID'];
$selected_customer_id = $customer_data->fields['PK_USER_MASTER'];
$selected_user_id = $customer_data->fields['PK_USER'];

$res = $db->Execute("SELECT * FROM DOA_USERS JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$CUSTOMER_ID'");

if ($res->RecordCount() == 0) {
    header("location:all_customers.php");
    exit;
}
$PK_USER = $res->fields['PK_USER'];
$PK_USER_MASTER = $res->fields['PK_USER_MASTER'];
$USER_NAME = $res->fields['USER_NAME'];
$FIRST_NAME = $res->fields['FIRST_NAME'];
$LAST_NAME = $res->fields['LAST_NAME'];
$EMAIL_ID = $res->fields['EMAIL_ID'];
$USER_IMAGE = $res->fields['USER_IMAGE'];
$GENDER = $res->fields['GENDER'];
$DOB = $res->fields['DOB'];
$ADDRESS = $res->fields['ADDRESS'];
$ADDRESS_1 = $res->fields['ADDRESS_1'];
$PK_COUNTRY = $res->fields['PK_COUNTRY'];
$PK_STATES = $res->fields['PK_STATES'];
$CITY = $res->fields['CITY'];
$ZIP = $res->fields['ZIP'];
$PHONE = $res->fields['PHONE'];
$NOTES = $res->fields['NOTES'];
$ACTIVE = $res->fields['ACTIVE'];
$PASSWORD = $res->fields['PASSWORD'];
$INACTIVE_BY_ADMIN = $res->fields['INACTIVE_BY_ADMIN'];
$CREATE_LOGIN = $res->fields['CREATE_LOGIN'];

$user_interest_other_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_INTEREST_OTHER_DATA` WHERE `PK_USER_MASTER` = '$CUSTOMER_ID'");
if ($user_interest_other_data->RecordCount() > 0) {
    $WHAT_PROMPTED_YOU_TO_INQUIRE = $user_interest_other_data->fields['WHAT_PROMPTED_YOU_TO_INQUIRE'];
    $PK_SKILL_LEVEL = $user_interest_other_data->fields['PK_SKILL_LEVEL'];
    $PK_INQUIRY_METHOD = $user_interest_other_data->fields['PK_INQUIRY_METHOD'];
    $INQUIRY_TAKER_ID = $user_interest_other_data->fields['INQUIRY_TAKER_ID'];
}

$customer_details = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = '$CUSTOMER_ID'");
if ($customer_details->RecordCount() > 0) {
    $PK_CUSTOMER_DETAILS = $customer_details->fields['PK_CUSTOMER_DETAILS'];
    $CALL_PREFERENCE = $customer_details->fields['CALL_PREFERENCE'];
    $REMINDER_OPTION = $customer_details->fields['REMINDER_OPTION'];
    $ATTENDING_WITH = $customer_details->fields['ATTENDING_WITH'];
    $PARTNER_FIRST_NAME = $customer_details->fields['PARTNER_FIRST_NAME'];
    $PARTNER_LAST_NAME = $customer_details->fields['PARTNER_LAST_NAME'];
    $PARTNER_PHONE = $customer_details->fields['PARTNER_PHONE'];
    $PARTNER_EMAIL = $customer_details->fields['PARTNER_EMAIL'];
    $PARTNER_GENDER = $customer_details->fields['PARTNER_GENDER'];
    $PARTNER_DOB = $customer_details->fields['PARTNER_DOB'];
}

$selected_primary_location = $db->Execute("SELECT PRIMARY_LOCATION_ID FROM DOA_USER_MASTER WHERE PK_USER_MASTER = '$CUSTOMER_ID'");
$primary_location = $selected_primary_location->fields['PRIMARY_LOCATION_ID'];

if ($PK_USER_MASTER > 0) {
    makeExpiryEnrollmentComplete($PK_USER_MASTER);
    makeMiscComplete($PK_USER_MASTER);
    makeDroppedCancelled($PK_USER_MASTER);
    checkAllEnrollmentStatus($PK_USER_MASTER);
}

?>

<style>
    #paymentModel {
        z-index: 500;
    }

    #commentModel {
        z-index: 500;
    }
</style>
<!-- CSS for Popup -->
<style>
    .popup {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        justify-content: center;
        align-items: center;
    }

    .popup-content {
        background-color: white;
        padding: 20px;
        border-radius: 10px;
        max-width: 80%;
        text-align: center;
    }

    .close {
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 30px;
        color: white;
        cursor: pointer;
    }
</style>
<!-- Nav tabs -->
<?php if (!empty($_GET['tab'])) { ?>
    <ul class="nav nav-tabs" role="tablist">
        <?php if ($_GET['tab'] == 'edit_appointment') { ?>
            <li> <a class="nav-link active" id="edit_appointment_tab_link" data-bs-toggle="tab" href="#edit_appointment" role="tab"><span class="hidden-sm-up"><i class="ti-id-badge"></i></span> <span class="hidden-xs-down">Edit Appointment</span></a> </li>
        <?php } ?>
        <?php if ($_GET['tab'] == 'profile') { ?>
            <li> <a class="nav-link" id="profile_tab_link" data-bs-toggle="tab" href="#profile" role="tab"><span class="hidden-sm-up"><i class="ti-id-badge"></i></span> <span class="hidden-xs-down">Profile</span></a> </li>
        <?php } ?>
        <?php if ($_GET['tab'] == 'appointment_view') { ?>
            <li> <a class="nav-link" id="appointment_view_tab_link" data-bs-toggle="tab" href="#appointment_view" role="tab"><span class="hidden-sm-up"><i class="ti-calendar"></i></span> <span class="hidden-xs-down">Appointments</span></a> </li>
        <?php } ?>
        <?php if ($_GET['tab'] == 'billing') { ?>
            <li> <a class="nav-link" id="billing_tab_link" data-bs-toggle="tab" href="#billing" role="tab"><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Billing</span></a> </li>
        <?php } ?>
        <?php if ($_GET['tab'] == 'comments') { ?>
            <li> <a class="nav-link" id="comment_tab_link" data-bs-toggle="tab" href="#comments" role="tab"><span class="hidden-sm-up"><i class="ti-comment"></i></span> <span class="hidden-xs-down">Comments</span></a> </li>
        <?php } ?>
    </ul>
<?php } else { ?>
    <ul class="nav nav-pills" role="tablist">
        <li> <a class="nav-link active" data-bs-toggle="tab" href="#edit_appointment" role="tab"><span class="hidden-sm-up"><i class="ti-id-badge"></i></span> <span class="hidden-xs-down">Edit Appointment</span></a> </li>
        <li> <a class="nav-link" data-bs-toggle="tab" href="#profile" role="tab"><span class="hidden-sm-up"><i class="ti-id-badge"></i></span> <span class="hidden-xs-down">Profile</span></a> </li>
        <li id="login_info_tab" style="display: <?= ($CREATE_LOGIN == 1) ? '' : 'none' ?>"> <a class="nav-link" id="login_info_tab_link" data-bs-toggle="tab" href="#login" role="tab"><span class="hidden-sm-up"><i class="ti-lock"></i></span> <span class="hidden-xs-down">Login Info</span></a> </li>
        <li> <a class="nav-link" data-bs-toggle="tab" href="#family" id="family_tab_link" role="tab"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Family</span></a> </li>
        <li> <a class="nav-link" data-bs-toggle="tab" href="#interest" id="interest_tab_link" role="tab"><span class="hidden-sm-up"><i class="ti-pencil-alt"></i></span> <span class="hidden-xs-down">Interests</span></a> </li>
        <li> <a class="nav-link" data-bs-toggle="tab" href="#document" id="document_tab_link" onclick="showAgreementDocument()" role="tab"><span class="hidden-sm-up"><i class="ti-files"></i></span> <span class="hidden-xs-down">Documents</span></a> </li>
        <li> <a class="nav-link" data-bs-toggle="tab" href="#enrollment" onclick="enrollmentLoadMore('normal')" role="tab"><span class="hidden-sm-up"><i class="ti-calendar"></i></span> <span class="hidden-xs-down">Active Enrollments</span></a> </li>
        <li> <a class="nav-link" data-bs-toggle="tab" href="#enrollment" onclick="enrollmentLoadMore('completed')" role="tab"><span class="hidden-sm-up"><i class="ti-view-list"></i></span> <span class="hidden-xs-down">Completed Enrollments</span></a> </li>
        <li> <a class="nav-link" data-bs-toggle="tab" href="#appointment_view" onclick="showAppointmentListView(1)" role="tab"><span class="hidden-sm-up"><i class="ti-calendar"></i></span> <span class="hidden-xs-down">Appointments</span></a> </li>
        <li> <a class="nav-link" data-bs-toggle="tab" href="#comments" id="comment_tab_link" role="tab"><span class="hidden-sm-up"><i class="ti-comment"></i></span> <span class="hidden-xs-down">Comments</span></a> </li>
        <li> <a class="nav-link" data-bs-toggle="tab" href="#payment_due" id="payment_due_tab_link" onclick="getPaymentDueList()" role="tab"><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Payment Due</span></a> </li>
    </ul>
<?php } ?>

<!-- Tab panes -->
<div class="tab-content tabcontent-border">
    <div class="tab-pane active" id="edit_appointment" role="tabpanel">
        <form class="form-material form-horizontal" id="appointment_form" action="includes/update_appointment_details.php" method="post" enctype="multipart/form-data">
            <input type="hidden" class="PK_USER_MASTER" name="PK_USER_MASTER" value="<?= $PK_USER_MASTER ?>">
            <input type="hidden" name="REDIRECT_URL" value="../all_schedules.php?date=<?= date('m/d/Y', strtotime($DATE)) ?>">
            <input type="hidden" name="PK_APPOINTMENT_MASTER" class="PK_APPOINTMENT_MASTER" value="<?= $PK_APPOINTMENT_MASTER ?>">
            <input type="hidden" name="APPOINTMENT_TYPE" class="APPOINTMENT_TYPE" value="<?= $APPOINTMENT_TYPE ?>>">
            <div style="padding-top: 10px;">
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Name: </label>
                            <p><a href="customer.php?id=<?= $selected_user_id ?>&master_id=<?= $selected_customer_id ?>&tab=profile" target="_blank" style="color: blue; font-weight: bold"><?= $selected_customer ?></a></p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Phone: </label>
                            <p><?= $customer_phone ?></p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Email: </label>
                            <p><?= $customer_email ?></p>
                        </div>
                    </div>
                </div>
                <?php if ($ATTENDING_WITH == 'With a Partner') { ?>
                    <div class="row" style="margin-top: -25px;">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Partner Name: </label>
                                <p><?= $PARTNER_FIRST_NAME ?> <?= $PARTNER_LAST_NAME ?></p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Phone: </label>
                                <p><?= $PARTNER_PHONE ?></p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Email: </label>
                                <p><?= $PARTNER_EMAIL ?></p>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="row">
                    <?php if ($SERVICE_NAME != 'For records only' && $APPOINTMENT_TYPE == 'NORMAL') { ?>
                        <div class="col-4" id="enrollment_div">
                            <div class="form-group">
                                <label class="form-label">Enrollment :
                                    <!--<span id="change_enrollment" style="margin-left: 30px;"><a href="javascript:" onclick="changeEnrollment()">Change</a></span>
                                        <span id="cancel_change_enrollment" style="margin-left: 30px; display: none;"><a href="javascript:;" onclick="cancelChangeEnrollment()">Cancel</a></span>-->
                                </label>
                                <select id="enrollment_select" class="form-control" required name="PK_ENROLLMENT_MASTER">
                                    <option value="">Select Enrollment ID</option>
                                    <?php
                                    $selected_enrollment = '';
                                    $row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_PACKAGE.PACKAGE_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.PK_LOCATION, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.CHARGE_TYPE, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID FROM DOA_ENROLLMENT_MASTER RIGHT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_PACKAGE ON DOA_ENROLLMENT_MASTER.PK_PACKAGE = DOA_PACKAGE.PK_PACKAGE WHERE DOA_SERVICE_MASTER.PK_SERVICE_CLASS != 5 AND DOA_SERVICE_CODE.IS_GROUP != 1 AND ((DOA_ENROLLMENT_MASTER.STATUS = 'A' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0) OR (DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = $PK_ENROLLMENT_MASTER)) AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $PK_USER_MASTER);
                                    while (!$row->EOF) {
                                        $name = $row->fields['ENROLLMENT_NAME'];
                                        if (empty($name)) {
                                            $enrollment_name = ' ';
                                        } else {
                                            $enrollment_name = "$name" . " - ";
                                        }

                                        $PACKAGE_NAME = $row->fields['PACKAGE_NAME'];
                                        if (empty($PACKAGE_NAME)) {
                                            $PACKAGE = ' ';
                                        } else {
                                            $PACKAGE = "$PACKAGE_NAME" . " || ";
                                        }

                                        if ($row->fields['CHARGE_TYPE'] == 'Membership') {
                                            $NUMBER_OF_SESSION = 99; //getAllSessionCreatedCount($row->fields['PK_ENROLLMENT_SERVICE'], 'NORMAL');
                                        } else {
                                            $NUMBER_OF_SESSION = $row->fields['NUMBER_OF_SESSION'];
                                        }

                                        $PRICE_PER_SESSION = $row->fields['PRICE_PER_SESSION'];
                                        $TOTAL_AMOUNT_PAID = ($row->fields['TOTAL_AMOUNT_PAID'] != null) ? $row->fields['TOTAL_AMOUNT_PAID'] : 0;
                                        $USED_SESSION_COUNT = getAllSessionCreatedCount($row->fields['PK_ENROLLMENT_SERVICE'], 'NORMAL');
                                        $paid_session = ($PRICE_PER_SESSION > 0) ? number_format(($TOTAL_AMOUNT_PAID / $PRICE_PER_SESSION), 2) : $NUMBER_OF_SESSION;

                                        if ($PK_ENROLLMENT_MASTER == $row->fields['PK_ENROLLMENT_MASTER']) {
                                            $selected_enrollment = $row->fields['ENROLLMENT_ID'];
                                        } ?>
                                        <option value="<?php echo $row->fields['PK_ENROLLMENT_MASTER'] . ',' . $row->fields['PK_ENROLLMENT_SERVICE'] . ',' . $row->fields['PK_SERVICE_MASTER'] . ',' . $row->fields['PK_SERVICE_CODE']; ?>" data-location_id="<?= $row->fields['PK_LOCATION'] ?>" data-no_of_session="<?= $NUMBER_OF_SESSION ?>" data-used_session="<?= $USED_SESSION_COUNT ?>" <?= (($NUMBER_OF_SESSION - $USED_SESSION_COUNT) <= 0) ? 'disabled' : '' ?> <?= ($PK_ENROLLMENT_SERVICE == $row->fields['PK_ENROLLMENT_SERVICE']) ? 'selected' : '' ?>><?= $enrollment_name . $row->fields['PK_ENROLLMENT_MASTER'] . ' || ' . $PACKAGE . $row->fields['SERVICE_NAME'] . ' || ' . $row->fields['SERVICE_CODE'] . ' || ' . $USED_SESSION_COUNT . '/' . $NUMBER_OF_SESSION . ' || Paid : ' . $paid_session; ?></option>
                                    <?php
                                        $row->MoveNext();
                                    } ?>
                                </select>
                                <!--<p class="enrollment_info"><?php /*=$selected_enrollment*/ ?></p>-->
                            </div>
                        </div>
                        <div class="col-4 enrollment_info">
                            <div class="form-group">
                                <label class="form-label">Enrollment ID: </label>
                                <p style="margin-top: 8px;"><?= $selected_enrollment ?></p>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="col-4 enrollment_info">
                        <div class="form-group">
                            <label class="form-label">Apt #: </label>
                            <p style="margin-top: 8px;"><?= $SERIAL_NUMBER ?></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-2 enrollment_info">
                        <div class="form-group">
                            <label class="form-label">Service : </label>
                            <select class="form-control" required name="PK_SERVICE_MASTER" id="PK_SERVICE_MASTER" style="display: none;" onchange="selectThisService(this);" disabled>
                                <option value="">Select Service</option>
                                <?php
                                $selected_service = '';
                                $row = $db_account->Execute("SELECT DISTINCT(DOA_SERVICE_MASTER.PK_SERVICE_MASTER), DOA_SERVICE_MASTER.SERVICE_NAME FROM DOA_SERVICE_MASTER");
                                while (!$row->EOF) {
                                    if ($PK_SERVICE_MASTER == $row->fields['PK_SERVICE_MASTER']) {
                                        $selected_service = $row->fields['SERVICE_NAME'];
                                    } ?>
                                    <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>" <?= ($PK_SERVICE_MASTER == $row->fields['PK_SERVICE_MASTER']) ? 'selected' : '' ?>><?= $row->fields['SERVICE_NAME'] ?></option>
                                <?php $row->MoveNext();
                                } ?>
                            </select>
                            <p style="margin-top: 8px;"><?= $selected_service ?></p>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="form-group">
                            <label class="form-label">Service Code : </label>
                            <?php if ($PK_SERVICE_CODE == 0) {
                                $row = $db_account->Execute("SELECT * FROM DOA_SERVICE_CODE WHERE IS_DEFAULT=1");
                                $service_code = $row->fields['SERVICE_CODE']; ?>
                                <p><?= $service_code ?></p>
                            <?php } else { ?>
                                <select class="form-control" required name="PK_SERVICE_CODE" id="PK_SERVICE_CODE" style="display: none;" onchange="getSlots()" disabled>
                                    <option value="">Select Service Code</option>
                                    <?php
                                    $selected_service_code = '';
                                    $row = $db_account->Execute("SELECT * FROM DOA_SERVICE_CODE WHERE PK_SERVICE_MASTER = " . $PK_SERVICE_MASTER);
                                    while (!$row->EOF) {
                                        if ($PK_SERVICE_CODE == $row->fields['PK_SERVICE_CODE']) {
                                            $selected_service_code = $row->fields['SERVICE_CODE'];
                                        } ?>
                                        <option value="<?php echo $row->fields['PK_SERVICE_CODE']; ?>" data-duration="<?= $row->fields['DURATION'] ?>" <?= ($PK_SERVICE_CODE == $row->fields['PK_SERVICE_CODE']) ? 'selected' : '' ?>><?= $row->fields['SERVICE_CODE'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            <?php } ?>
                            <p style="margin-top: 8px;"><?= $selected_service_code ?></p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Scheduling Code : <!--<span id="change_scheduling_code" style="margin-left: 30px;"><a href="javascript:;" onclick="changeSchedulingCode()">Change</a></span>
                                    <span id="cancel_change_scheduling_code" style="margin-left: 30px; display: none;"><a href="javascript:;" onclick="cancelChangeSchedulingCode()">Cancel</a></span></label>-->
                                <div id="scheduling_code_select">
                                    <select class="form-control" required name="PK_SCHEDULING_CODE" id="PK_SCHEDULING_CODE">
                                        <option value="">Select Scheduling Code</option>
                                        <?php
                                        $selected_scheduling_code = '';
                                        $row = $db_account->Execute("SELECT DOA_SCHEDULING_CODE.`PK_SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_NAME`, DOA_SCHEDULING_CODE.`DURATION` FROM `DOA_SCHEDULING_CODE` LEFT JOIN DOA_SCHEDULING_SERVICE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE=DOA_SCHEDULING_SERVICE.PK_SCHEDULING_CODE WHERE DOA_SCHEDULING_CODE.`ACTIVE` = 1 AND DOA_SCHEDULING_SERVICE.PK_SERVICE_MASTER=" . $PK_SERVICE_MASTER);
                                        while (!$row->EOF) {
                                            if ($PK_SCHEDULING_CODE == $row->fields['PK_SCHEDULING_CODE']) {
                                                $selected_scheduling_code = $row->fields['SCHEDULING_CODE'];
                                            } ?>
                                            <option value="<?php echo $row->fields['PK_SCHEDULING_CODE']; ?>" <?= ($PK_SCHEDULING_CODE == $row->fields['PK_SCHEDULING_CODE']) ? 'selected' : '' ?>><?= $row->fields['SCHEDULING_CODE'] ?></option>
                                        <?php $row->MoveNext();
                                        } ?>
                                    </select>
                                </div>
                                <!--<p id="scheduling_code_name"><?php /*=$selected_scheduling_code*/ ?></p>-->
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label"><?= $service_provider_title ?> : <!--<span id="change_service_provider" style="margin-left: 30px;"><a href="javascript:;" onclick="changeServiceProvider()">Change</a></span>
                                    <span id="cancel_change_service_provider" style="margin-left: 30px; display: none;"><a href="javascript:;" onclick="cancelChangeServiceProvider()">Cancel</a></span></label>-->
                                <div id="service_provider_select">
                                    <select class="form-control" required name="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID" onchange="getSlots()">
                                        <option value="">Select <?= $service_provider_title ?></option>
                                        <?php
                                        $selected_service_provider = '';
                                        $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USER_ROLES.PK_ROLES IN(5) AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
                                        while (!$row->EOF) {
                                            if ($SERVICE_PROVIDER_ID == $row->fields['PK_USER']) {
                                                $selected_service_provider = $row->fields['NAME'];
                                            } ?>
                                            <option value="<?php echo $row->fields['PK_USER']; ?>" <?= ($SERVICE_PROVIDER_ID == $row->fields['PK_USER']) ? 'selected' : '' ?>><?= $row->fields['NAME'] ?></option>
                                        <?php $row->MoveNext();
                                        } ?>
                                    </select>
                                </div>
                                <!--<p id="service_provider_name"><?php /*=$selected_service_provider*/ ?></p>-->
                        </div>
                    </div>
                </div>
                <!--<span id="cancel_reschedule" style="display: none;"><a href="javascript:;" onclick="cancelReschedule()">Cancel</a></span>-->
                <div class="row" id="date_time_div">
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Date : </label>
                            <p><?= $DATE ?></p>
                        </div>
                    </div>
                    <!--<div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Time : </label>
                                <p><?php /*=date('h:i A', strtotime($START_TIME)).' - '.date('h:i A', strtotime($END_TIME))*/ ?></p>
                            </div>
                        </div>-->
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Start Time</label>
                            <input type="text" id="START_TIME" name="START_TIME" class="form-control timepicker-normal" value="<?php echo ($START_TIME) ? date('h:i A', strtotime($START_TIME)) : '' ?>">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">End Time</label>
                            <input type="text" id="END_TIME" name="END_TIME" class="form-control timepicker-normal" value="<?php echo ($END_TIME) ? date('h:i A', strtotime($END_TIME)) : '' ?>">
                        </div>
                    </div>
                    <!--<div class="col-3">
                            <div class="form-group">
                                <span><a href="javascript:;" onclick="cancelAppointment()">Cancel</a></span>
                            </div>
                        </div>-->
                </div>

                <!--<input type="hidden" name="DATE" id="DATE">
                <input type="hidden" name="START_TIME" id="START_TIME">
                <input type="hidden" name="END_TIME" id="END_TIME">

                <div class="row" id="schedule_div" style="display: none;">
                    <div class="col-7">
                        <div id="showcase-wrapper">
                            <div id="myCalendarWrapper">
                            </div>
                        </div>
                    </div>

                    <div class="col-5" style="background-color: #add1b1;max-height:470px;overflow-y:scroll;" >
                        <br />
                        <div class="row" id="slot_div" >

                        </div>
                    </div>
                </div>-->

                <div class="row m-t-25">
                    <input type="hidden" name="PK_APPOINTMENT_STATUS_OLD" value="<?= $PK_APPOINTMENT_STATUS ?>">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Status : <?php /*if($PK_APPOINTMENT_STATUS!=2) {*/ ?><!--<span id="change_status" style="margin-left: 30px;"><a href="javascript:;" onclick="changeStatus()">Change</a></span><?php /*}*/ ?>
                                <span id="cancel_change_status" style="margin-left: 30px; display: none;"><a href="javascript:;" onclick="cancelChangeStatus()">Cancel</a></span>--></label><br>
                            <select class="form-control" name="PK_APPOINTMENT_STATUS_NEW" id="PK_APPOINTMENT_STATUS" onchange="changeAppointmentStatus(this)" <?= ($PK_APPOINTMENT_STATUS == 2) ? 'disabled' : '' ?>>
                                <option value="1">Select Status</option>
                                <?php
                                $selected_status = '';
                                $row = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_STATUS` WHERE `ACTIVE` = 1");
                                while (!$row->EOF) {
                                    if ($PK_APPOINTMENT_STATUS == $row->fields['PK_APPOINTMENT_STATUS']) {
                                        $selected_status = $row->fields['APPOINTMENT_STATUS'];
                                    } ?>
                                    <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS']; ?>" <?= ($PK_APPOINTMENT_STATUS == $row->fields['PK_APPOINTMENT_STATUS']) ? 'selected' : '' ?>><?= $row->fields['APPOINTMENT_STATUS'] ?></option>
                                <?php $row->MoveNext();
                                } ?>
                            </select>
                            <!--<p id="appointment_status"><?php /*=$selected_status*/ ?></p>-->
                        </div>
                    </div>

                    <?php if ($SERVICE_NAME != 'For records only') { ?>
                        <div class="col-6">
                            <input type="hidden" name="IS_CHARGED_OLD" value="<?= $IS_CHARGED ?>">
                            <div class="form-group">
                                <label class="form-label">Payment Status</label>
                                <select class="form-control" name="IS_CHARGED" id="IS_CHARGED">
                                    <option value="1" <?= ($IS_CHARGED == 1) ? 'selected' : '' ?>>Charge</option>
                                    <option value="0" <?= ($IS_CHARGED == 0) ? 'selected' : '' ?>>No charge</option>
                                </select>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="row" id="no_show_div" style="display: <?= ($PK_APPOINTMENT_STATUS == 4) ? '' : 'none' ?>;">
                    <div class="col-8">
                        <div class="form-group">
                            <label class="form-label">No Show</label>
                            <select class="form-control" name="NO_SHOW">
                                <option value="">Select</option>
                                <option value="Charge" <?= ($NO_SHOW == 'Charge') ? 'selected' : '' ?>>Charge</option>
                                <option value="No Charge" <?= ($NO_SHOW == 'No Charge') ? 'selected' : '' ?>>No Charge</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row" id="add_info_div">
                    <?php if ($SERVICE_NAME != 'For records only') { ?>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" style="color: red;">Comments (Visual for client)</label>
                                <textarea class="form-control" name="COMMENT" rows="4"><?= $COMMENT ?></textarea><span><?= $CHANGED_BY ?></span>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Internal Comment</label>
                            <textarea class="form-control" name="INTERNAL_COMMENT" rows="4"><?= $INTERNAL_COMMENT ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Upload Image 1</label>
                            <input type="file" class="form-control" name="IMAGE" id="IMAGE">
                            <img src="<?= $IMAGE ?>" onclick="showPopup('image', '<?= $IMAGE ?>')" style="cursor: pointer; margin-top: 10px; max-width: 150px; height: auto;">
                            <?php if ((in_array('Calendar Delete', $PERMISSION_ARRAY) || in_array('Appointments Delete', $PERMISSION_ARRAY)) && ($IMAGE != '')) { ?>
                                <a href="javascript:" onclick='ConfirmDeleteImage(<?= $PK_APPOINTMENT_MASTER ?>, 1);'><i class="fa fa-trash"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Video 1 -->
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Upload Video 1</label>
                            <input type="file" class="form-control" name="VIDEO" id="VIDEO" accept="video/*">
                            <?php if ($VIDEO != '') { ?>
                                <div style="display: flex; align-items: center; gap: 4px; margin-top: 10px">
                                    <video width="240" height="135" controls onclick="showPopup('video', '<?= $VIDEO ?>')" style="cursor: pointer;">
                                        <source src="<?= $VIDEO ?>" type="video/mp4">
                                    </video>
                                    <?php if (in_array('Calendar Delete', $PERMISSION_ARRAY) || in_array('Appointments Delete', $PERMISSION_ARRAY)) { ?>
                                        <a href="javascript:" onclick='ConfirmDeleteVideo(<?= $PK_APPOINTMENT_MASTER ?>, 1);'><i class="fa fa-trash"></i></a>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Image 2 -->
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Upload Image 2</label>
                            <input type="file" class="form-control" name="IMAGE_2" id="IMAGE_2">
                            <img src="<?= $IMAGE_2 ?>" onclick="showPopup('image', '<?= $IMAGE_2 ?>')" style="cursor: pointer; margin-top: 10px; max-width: 150px; height: auto;">
                            <?php if ((in_array('Calendar Delete', $PERMISSION_ARRAY) || in_array('Appointments Delete', $PERMISSION_ARRAY)) && ($IMAGE_2 != '')) { ?>
                                <a href="javascript:" onclick='ConfirmDeleteImage(<?= $PK_APPOINTMENT_MASTER ?>, 2);'><i class="fa fa-trash"></i></a>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Video 2 -->
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Upload Video 2</label>
                            <input type="file" class="form-control" name="VIDEO_2" id="VIDEO_2" accept="video/*">
                            <?php if ($VIDEO_2 != '') { ?>
                                <div style="display: flex; align-items: center; gap: 4px; margin-top: 10px">
                                    <video width="240" height="135" controls onclick="showPopup('video', '<?= $VIDEO_2 ?>')" style="cursor: pointer;">
                                        <source src="<?= $VIDEO_2 ?>" type="video/mp4">
                                    </video>
                                    <?php if (in_array('Calendar Delete', $PERMISSION_ARRAY) || in_array('Appointments Delete', $PERMISSION_ARRAY)) { ?>
                                        <a href="javascript:" onclick='ConfirmDeleteVideo(<?= $PK_APPOINTMENT_MASTER ?>, 2);'><i class="fa fa-trash"></i></a>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <!-- Popup Modal -->
                <div id="mediaPopup" class="popup" onclick="closePopup()">
                    <span class="close" onclick="closePopup()">&times;</span>
                    <div class="popup-content" onclick="event.stopPropagation();">
                        <img id="popupImage" src="" style="display:none; max-width: 100%;">
                        <video id="popupVideo" controls style="display:none; max-width: 100%;">
                            <source id="popupVideoSource" src="" type="video/mp4">
                        </video>
                    </div>
                </div>

                <div id="modal" class="hidden modal">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <div class="modal-content">
                        <img id="modalImage" class="hidden">
                        <video id="modalVideo" class="hidden" controls></video>
                    </div>
                </div>

                <?php if ($STANDING_ID > 0) { ?>
                    <div class="form-group">
                        <label><input type="checkbox" name="STANDING_ID" value="<?= $STANDING_ID ?>"> All Session Details Will Be Changed</label>
                    </div>
                <?php } ?>

                <div class="form-group" style="margin-top: 25px;">
                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">SAVE</button>
                    <a onclick="closeEditAppointment()" class="btn btn-inverse waves-effect waves-light">Cancel</a>
                    <a href="enrollment.php?master_id_customer=<?= $selected_customer_id; ?>" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">Enroll</a>
                    <!--<a href="customer.php?id=<?php /*=$selected_user_id*/ ?>&master_id=<?php /*=$selected_customer_id*/ ?>&tab=billing" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">Pay</a>
                    <a href="customer.php?id=<?php /*=$selected_user_id*/ ?>&master_id=<?php /*=$selected_customer_id*/ ?>&tab=appointment" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">View Appointment</a>-->
                    <a onclick="deleteThisAppointment(<?= $PK_APPOINTMENT_MASTER ?>)" class="btn btn-danger waves-effect waves-light"><i class="ti-trash"></i> Delete</a>
                </div>
            </div>
        </form>
    </div>

    <div class="tab-pane" id="profile" role="tabpanel">
        <form class="form-material form-horizontal" id="profile_form">
            <input type="hidden" name="FUNCTION_NAME" value="saveProfileData">
            <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
            <input type="hidden" class="PK_USER_MASTER" name="PK_USER_MASTER" value="<?= $PK_USER_MASTER ?>">
            <input type="hidden" class="TYPE" name="TYPE" value="2">
            <div class="p-20">
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">First Name<span class="text-danger">*</span></label>
                            <div class="col-md-12">
                                <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" required value="<?= $FIRST_NAME ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <div class="col-md-12">
                                <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name" value="<?= $LAST_NAME ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="form-group">
                            <label class="form-label">Customer ID</label>
                            <div class="col-md-12">
                                <input type="text" id="CUSTOMER_ID" name="CUSTOMER_ID" class="form-control" placeholder="Enter User Name" value="<?= $USER_NAME ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <input type="hidden" name="PK_ROLES[]" value="4">
                    </div>
                </div>

                <div class="row">
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">Phone<span class="text-danger" id="phone_label"><?= ($CREATE_LOGIN == 1) ? '*' : '' ?></span></label>
                            <div class="col-md-12">
                                <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone Number" value="<?php echo $PHONE ?>" <?= ($CREATE_LOGIN == 1) ? 'required' : '' ?>>
                            </div>
                        </div>
                    </div>
                    <div class="col-2" style="width: 15%;">
                        <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 22px;" onclick="addMorePhone();"><i class="ti-plus"></i> New</a>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">Email<span class="text-danger" id="email_label"><?= ($CREATE_LOGIN == 1) ? '*' : '' ?></span></label>
                            <div class="col-md-12">
                                <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" value="<?= $EMAIL_ID ?>" <?= ($CREATE_LOGIN == 1) ? 'required' : '' ?>>
                            </div>
                        </div>
                    </div>
                    <div class="col-2" style="width: 15%;">
                        <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 22px;" onclick="addMoreEmail();"><i class="ti-plus"></i> New</a>
                    </div>
                    <div class="col-2" style="width: 18%;">
                        <label class="col-md-12 mt-3"><input type="checkbox" id="CREATE_LOGIN" name="CREATE_LOGIN" class="form-check-inline" <?= ($CREATE_LOGIN == 1) ? 'checked' : '' ?> style="margin-top: 15px;" onchange="createLogin(this);"> Create Login</label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-5" id="add_more_phone">
                        <?php
                        if (!empty($_GET['id'])) {
                            $customer_phone = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_PHONE WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                            while (!$customer_phone->EOF) { ?>
                                <div class="row">
                                    <div class="col-9">
                                        <div class="form-group">
                                            <label class="form-label">Phone</label>
                                            <div class="col-md-12">
                                                <input type="text" name="CUSTOMER_PHONE[]" class="form-control" placeholder="Enter Phone Number" value="<?= $customer_phone->fields['PHONE'] ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-2" style="padding-top: 25px;">
                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                    </div>
                                </div>
                            <?php $customer_phone->MoveNext();
                            } ?>
                        <?php } ?>
                    </div>
                    <div class="col-5" id="add_more_email">
                        <?php
                        if (!empty($_GET['id'])) {
                            $customer_email = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_EMAIL WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                            while (!$customer_email->EOF) { ?>
                                <div class="row">
                                    <div class="col-9">
                                        <div class="form-group">
                                            <label class="col-md-12">Email</label>
                                            <div class="col-md-12">
                                                <input type="email" name="CUSTOMER_EMAIL[]" class="form-control" placeholder="Enter Email Address" value="<?= $customer_email->fields['EMAIL'] ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-2" style="padding-top: 25px;">
                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                    </div>
                                </div>
                            <?php $customer_email->MoveNext();
                            } ?>
                        <?php } ?>
                    </div>
                </div>

                <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?= $PK_CUSTOMER_DETAILS ?>">
                <div class="row">
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">Call Preference</label>
                            <div class="col-md-12">
                                <select class="form-control" name="CALL_PREFERENCE">
                                    <option>Select</option>
                                    <option value="email" <?php if ($CALL_PREFERENCE == "email") echo 'selected = "selected"'; ?>>Email</option>
                                    <option value="text message" <?php if ($CALL_PREFERENCE == "text message") echo 'selected = "selected"'; ?>>Text Message</option>
                                    <option value="phone call" <?php if ($CALL_PREFERENCE == "phone call") echo 'selected = "selected"'; ?>>Phone Call</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-9">
                        <div class="form-group">
                            <label class="form-label">Reminder Options</label>
                            <div class="row m-t-10">
                                <div class="col-md-4">
                                    <label><input type="checkbox" class="form-check-inline" name="REMINDER_OPTION[]" <?= in_array('Email', explode(',', $REMINDER_OPTION)) ? 'checked' : '' ?> value="Email"> Email</label>
                                </div>
                                <div class="col-md-4">
                                    <label><input type="checkbox" class="form-check-inline" name="REMINDER_OPTION[]" <?= in_array('Text Message', explode(',', $REMINDER_OPTION)) ? 'checked' : '' ?> value="Text Message"> Text Message</label>
                                </div>
                                <div class="col-md-4">
                                    <label><input type="checkbox" class="form-check-inline" name="REMINDER_OPTION[]" <?= in_array('Phone Call', explode(',', $REMINDER_OPTION)) ? 'checked' : '' ?> value="Phone Call"> Phone Call</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select class="form-control" id="GENDER" name="GENDER">
                                <option>Select Gender</option>
                                <option value="Male" <?php if ($GENDER == "Male") echo 'selected = "selected"'; ?>>Male</option>
                                <option value="Female" <?php if ($GENDER == "Female") echo 'selected = "selected"'; ?>>Female</option>
                                <option value="Other" <?php if ($GENDER == "Other") echo 'selected = "selected"'; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="text" class="form-control datepicker-past" id="DOB" name="DOB" value="<?= ($DOB == '' || $DOB == '0000-00-00') ? '' : date('m/d/Y', strtotime($DOB)) ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="col-md-12">Address</label>
                            <div class="col-md-12">
                                <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="col-md-12">Apt/Ste</label>
                            <div class="col-md-12">
                                <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS_1 ?>">

                            </div>
                        </div>

                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="col-md-12">Country</label>
                            <div class="col-md-12">
                                <div class="col-sm-12">
                                    <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)">
                                        <option>Select Country</option>
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
                            <label class="col-md-12">State</label>
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
                            <label class="col-md-12">City</label>
                            <div class="col-md-12">
                                <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter your city" value="<?php echo $CITY ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="col-md-12">Postal / Zip Code</label>
                            <div class="col-md-12">
                                <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Postal / Zip Code" value="<?php echo $ZIP ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <label class="col-md-12">Preferred Location</label>
                        <div class="col-md-12 multiselect-box" style="width: 100%;">
                            <select class="multi_sumo_select" name="PK_USER_LOCATION[]" id="PK_LOCATION_MULTIPLE" multiple>
                                <?php
                                $selected_location = [];

                                $selected_location_row = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` WHERE `PK_USER` = '$PK_USER'");
                                while (!$selected_location_row->EOF) {
                                    $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                                    $selected_location_row->MoveNext();
                                }

                                $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                while (!$row->EOF) { ?>
                                    <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= in_array($row->fields['PK_LOCATION'], $selected_location) ? "selected" : "" ?>><?= $row->fields['LOCATION_NAME'] ?></option>
                                <?php $row->MoveNext();
                                } ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-6">
                        <label class="col-md-12">Primary Location<span class="text-danger">*</span></label>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <select class="form-control" name="PRIMARY_LOCATION_ID" id="PK_LOCATION_SINGLE" required>
                                <option value="">Select Primary Location</option>
                                <?php
                                $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                while (!$row->EOF) { ?>
                                    <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($primary_location == $row->fields['PK_LOCATION']) ? "selected" : "" ?>><?= $row->fields['LOCATION_NAME'] ?></option>
                                <?php $row->MoveNext();
                                } ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label class="col-md-12">Remarks</label>
                            <div class="col-md-12">
                                <textarea class="form-control" rows="3" id="NOTES" name="NOTES"><?php echo $NOTES ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="row">
                    <div class="col-2" style="margin-left: 80%">
                        <div class="form-group">
                            <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 15px;" onclick="addMoreSpecialDays(this);"><i class="ti-plus"></i> New</a>
                        </div>
                    </div>
                </div>
                <div class="add_more_special_days">
                    <?php
                    $customer_special_date = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_SPECIAL_DATE WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                    if ($customer_special_date->RecordCount() > 0) {
                        while (!$customer_special_date->EOF) { ?>
                            <div class="row">
                                <div class="col-5">
                                    <div class="form-group">
                                        <label class="form-label">Special Date</label>
                                        <div class="col-md-12">
                                            <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="CUSTOMER_SPECIAL_DATE[]" value="<?= $customer_special_date->fields['SPECIAL_DATE'] ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="form-group">
                                        <label class="form-label">Date Name</label>
                                        <div class="col-md-12">
                                            <input type="text" class="form-control" name="CUSTOMER_SPECIAL_DATE_NAME[]" value="<?= $customer_special_date->fields['DATE_NAME'] ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-2" style="padding-top: 25px;">
                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                </div>
                            </div>
                        <?php $customer_special_date->MoveNext();
                        } ?>
                    <?php } else { ?>
                        <div class="row">
                            <div class="col-5">
                                <div class="form-group">
                                    <label class="form-label">Special Date</label>
                                    <div class="col-md-12">
                                        <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="CUSTOMER_SPECIAL_DATE[]">
                                    </div>
                                </div>
                            </div>
                            <div class="col-5">
                                <div class="form-group">
                                    <label class="form-label">Date Name</label>
                                    <div class="col-md-12">
                                        <input type="text" class="form-control" name="CUSTOMER_SPECIAL_DATE_NAME[]">
                                    </div>
                                </div>
                            </div>
                            <div class="col-2" style="padding-top: 25px;">
                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <hr>


                <div class="form-group">
                    <div class="row">
                        <div class="col-4">
                            <label class="form-label">Will you be attending your lessons</label>
                        </div>
                        <div class="col-2">
                            <label><input type="radio" name="ATTENDING_WITH" class="form-check-inline" onclick="($(this).is(':checked'))?$('#partner_details').slideUp():$('#partner_details').slideDown()" value="Solo" <?= (($ATTENDING_WITH == '') ? 'checked' : (($ATTENDING_WITH == 'Solo') ? 'checked' : '')) ?>> Solo</label>
                        </div>
                        <div class="col-4">
                            <label><input type="radio" name="ATTENDING_WITH" class="form-check-inline" onclick="($(this).is(':checked'))?$('#partner_details').slideDown():$('#partner_details').slideUp()" value="With a Partner" <?= (($ATTENDING_WITH == 'With a Partner') ? 'checked' : '') ?>> With a Partner</label>
                        </div>
                    </div>
                </div>

                <div id="partner_details" style="display: <?= (($ATTENDING_WITH == 'With a Partner') ? '' : 'none') ?>;">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Partner's First Name<span class="text-danger">*</span></label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" placeholder="Enter Partner's First Name" name="PARTNER_FIRST_NAME" value="<?= $PARTNER_FIRST_NAME ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Partner's Last Name</label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" placeholder="Enter Partner's Last Name" name="PARTNER_LAST_NAME" value="<?= $PARTNER_LAST_NAME ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Partner's Gender</label>
                                <select class="form-control" id="PARTNER_GENDER" name="PARTNER_GENDER">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?= (($PARTNER_GENDER == 'Male') ? 'selected' : '') ?>>Male</option>
                                    <option value="Female" <?= (($PARTNER_GENDER == 'Female') ? 'selected' : '') ?>>Female</option>
                                    <option value="Other" <?= (($PARTNER_GENDER == 'Other') ? 'selected' : '') ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Partner's Date of Birth</label>
                                <input type="text" class="form-control datepicker-past" name="PARTNER_DOB" value="<?= ($PARTNER_DOB == '' || $PARTNER_DOB == '0000-00-00') ? '' : date('m/d/Y', strtotime($PARTNER_DOB)) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="col-md-12">Image Upload</label>
                            <div class="col-md-12">
                                <input type="file" name="USER_IMAGE" id="USER_IMAGE" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <?php if ($USER_IMAGE != '') { ?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $USER_IMAGE; ?>" data-fancybox-group="gallery"><img src="<?php echo $USER_IMAGE; ?>" style="width:120px; height:120px" /></a></div><?php } ?>
                        </div>
                    </div>
                </div>

                <div class="row <?= ($INACTIVE_BY_ADMIN == 1) ? 'div_inactive' : '' ?>" style="margin-bottom: 15px; margin-top: 15px;">
                    <div class="col-md-1">
                        <label class="form-label">Active : </label>
                    </div>
                    <div class="col-md-4">
                        <label><input type="radio" name="ACTIVE" id="ACTIVE_CUSTOMER" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <label><input type="radio" name="ACTIVE" id="ACTIVE_CUSTOMER" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?= empty($_GET['id']) ? 'Continue' : 'Save' ?></button>
                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
            </div>
        </form>
    </div>

    <div class="tab-pane" id="login" role="tabpanel">
        <form id="login_form">
            <input type="hidden" name="FUNCTION_NAME" value="saveLoginData">
            <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
            <input type="hidden" class="TYPE" name="TYPE" value="2">
            <div class="p-20">
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="col-md-12">User Name</label>
                            <div class="col-md-12">
                                <input type="text" id="USER_NAME" name="USER_NAME" class="form-control" placeholder="Enter User Name" onkeyup="ValidateUsername()" value="<?= $USER_NAME ?>">
                                <a class="btn-link" onclick="$('#change_password_div').slideToggle();">Change Password</a>
                            </div>
                        </div>
                        <span id="lblError" style="color: red"></span>

                    </div>
                </div>

                <?php if ($PASSWORD == '') { ?>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="col-md-12">Password</label>
                                <div class="col-md-12">
                                    <input type="password" required class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="PASSWORD" id="PASSWORD" onkeyup="isGood(this.value)">
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="col-md-12">Confirm Password</label>
                                <div class="col-md-12">
                                    <input type="password" required class="form-control" placeholder="Confirm Password" aria-label="Password" aria-describedby="basic-addon3" name="CONFIRM_PASSWORD" id="CONFIRM_PASSWORD" onkeyup="isGood(this.value)">
                                </div>
                            </div>
                        </div>
                    </div>
                    <b id="password_error" style="color: red;"></b>
                    <div class="row">
                        <div class="col-12">
                            <span style="color: orange;">Note : Password Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-2">
                            Password Strength:
                        </div>
                        <div class="col-3">
                            <small id="password-text"></small>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="row">
                        <div class="row" id="change_password_div" style="padding: 20px 20px 0px 20px; display: none;">
                            <div class="col-3">
                                <div class="form-group">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="PASSWORD" class="form-control" id="PASSWORD">
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-group">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="CONFIRM_PASSWORD" class="form-control" id="CONFIRM_PASSWORD">
                                </div>
                            </div>
                        </div>
                        <b id="password_error" style="color: red;"></b>
                    </div>
                <?php } ?>

                <div class="row <?= ($INACTIVE_BY_ADMIN == 1) ? 'div_inactive' : '' ?>" style="margin-bottom: 15px; margin-top: 15px;">
                    <div class="col-md-1">
                        <label class="form-label">Active : </label>
                    </div>
                    <div class="col-md-4">
                        <label><input type="radio" name="ACTIVE" id="ACTIVE_CUSTOMER" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <label><input type="radio" name="ACTIVE" id="ACTIVE_CUSTOMER" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?= empty($_GET['id']) ? 'Continue' : 'Save' ?></button>
                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
            </div>
        </form>
    </div>

    <?php $family_member_count = 0; ?>
    <div class="tab-pane" id="family" role="tabpanel">
        <form id="family_form">
            <input type="hidden" name="FUNCTION_NAME" value="saveFamilyData">
            <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
            <input type="hidden" class="PK_USER_MASTER" name="PK_USER_MASTER" value="<?= $PK_USER_MASTER ?>">
            <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?= $PK_CUSTOMER_DETAILS ?>">
            <input type="hidden" class="TYPE" name="TYPE" value="2">
            <div class="row" style="margin-bottom: 25px;">
                <a href="javascript:;" style="float: right; margin-left: 91%; margin-top: 10px; color: green;" onclick="addMoreFamilyMember();"><b><i class="ti-plus"></i> New</b></a>
            </div>
            <?php
            $family_member_details = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_DETAILS WHERE PK_CUSTOMER_PRIMARY = '$PK_CUSTOMER_DETAILS' AND IS_PRIMARY = 0");
            if ($PK_CUSTOMER_DETAILS > 0 && $family_member_details->RecordCount() > 0) {
                while (!$family_member_details->EOF) { ?>
                    <div class="row family_member" style="padding: 35px; margin-top: -60px;">
                        <div class="row">
                            <div class="col-3">
                                <div class="form-group">
                                    <label class="form-label">First Name<span class="text-danger">*</span></label>
                                    <div class="col-md-12">
                                        <input type="text" name="FAMILY_FIRST_NAME[]" class="form-control" placeholder="Enter First Name" value="<?= $family_member_details->fields['FIRST_NAME'] ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-group">
                                    <label class="form-label">Last Name</label>
                                    <div class="col-md-12">
                                        <input type="text" name="FAMILY_LAST_NAME[]" class="form-control" placeholder="Enter Last Name" value="<?= $family_member_details->fields['LAST_NAME'] ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-group">
                                    <label class="form-label">Relationship</label>
                                    <div class="col-md-12">
                                        <select class="form-control" name="PK_RELATIONSHIP[]">
                                            <option>Select Relationship</option>
                                            <?php
                                            $row = $db->Execute("SELECT * FROM DOA_RELATIONSHIP WHERE ACTIVE = 1");
                                            while (!$row->EOF) { ?>
                                                <option value="<?php echo $row->fields['PK_RELATIONSHIP']; ?>" <?= ($family_member_details->fields['PK_RELATIONSHIP'] == $row->fields['PK_RELATIONSHIP']) ? 'selected' : '' ?>><?= $row->fields['RELATIONSHIP'] ?></option>
                                            <?php $row->MoveNext();
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-2">
                                <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="$(this).closest('.row').next().slideToggle();"><i class="ti-arrow-circle-down"></i> More Info</a>
                            </div>
                            <div class="col-1">
                                <a href="javascript:;" class="btn btn-danger waves-effect waves-light text-white" style="margin-top: 30px;" onclick="removeThisFamilyMember(this);"><b><i class="ti-trash"></i></b></a>
                            </div>
                        </div>

                        <div style="display: none;">
                            <div class="row">
                                <div class="col-5">
                                    <div class="form-group">
                                        <label class="form-label">Phone</label>
                                        <div class="col-md-12">
                                            <input type="text" name="FAMILY_PHONE[]" class="form-control" placeholder="Enter Phone Number" value="<?= $family_member_details->fields['PHONE'] ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="form-group">
                                        <label class="col-md-12">Email</label>
                                        <div class="col-md-12">
                                            <input type="email" name="FAMILY_EMAIL[]" class="form-control" placeholder="Enter Email Address" value="<?= $family_member_details->fields['EMAIL'] ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Gender</label>
                                        <select class="form-control" name="FAMILY_GENDER[]">
                                            <option>Select Gender</option>
                                            <option value="Male" <?php if ($family_member_details->fields['GENDER'] == "Male") echo 'selected = "selected"'; ?>>Male</option>
                                            <option value="Female" <?php if ($family_member_details->fields['GENDER'] == "Female") echo 'selected = "selected"'; ?>>Female</option>
                                            <option value="Other" <?php if ($family_member_details->fields['GENDER'] == "Other") echo 'selected = "selected"'; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="text" class="form-control datepicker-past" name="FAMILY_DOB[]" value="<?= ($family_member_details->fields['DOB'] == '' || $family_member_details->fields['DOB'] == '0000-00-00') ? '' : date('m/d/Y', strtotime($family_member_details->fields['DOB'])) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-2" style="margin-left: 80%">
                                    <div class="form-group">
                                        <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 15px;" data-counter="<?= $family_member_count ?>" onclick="addMoreSpecialDaysFamily(this);"><i class="ti-plus"></i> New</a>
                                    </div>
                                </div>
                            </div>
                            <div class="add_more_special_days">
                                <?php
                                $family_special_date = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_SPECIAL_DATE WHERE PK_CUSTOMER_DETAILS = " . $family_member_details->fields['PK_CUSTOMER_DETAILS']);
                                if ($family_special_date->RecordCount() > 0) {
                                    while (!$family_special_date->EOF) { ?>
                                        <div class="row">
                                            <div class="col-5">
                                                <div class="form-group">
                                                    <label class="form-label">Special Date</label>
                                                    <div class="col-md-12">
                                                        <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="FAMILY_SPECIAL_DATE[<?= $family_member_count ?>][]" value="<?= $family_special_date->fields['SPECIAL_DATE'] ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-5">
                                                <div class="form-group">
                                                    <label class="form-label">Date Name</label>
                                                    <div class="col-md-12">
                                                        <input type="text" class="form-control" name="FAMILY_SPECIAL_DATE_NAME[<?= $family_member_count ?>][]" value="<?= $family_special_date->fields['DATE_NAME'] ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-2" style="padding-top: 25px;">
                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                            </div>
                                        </div>
                                    <?php $family_special_date->MoveNext();
                                    } ?>
                                <?php } else { ?>
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="form-group">
                                                <label class="form-label">Special Date</label>
                                                <div class="col-md-12">
                                                    <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="FAMILY_SPECIAL_DATE[<?= $family_member_count ?>][]">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="form-group">
                                                <label class="form-label">Date Name</label>
                                                <div class="col-md-12">
                                                    <input type="text" class="form-control" name="FAMILY_SPECIAL_DATE_NAME[<?= $family_member_count ?>][]">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-2" style="padding-top: 25px;">
                                            <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php $family_member_details->MoveNext();
                    $family_member_count++;
                } ?>
            <?php } elseif (empty($_GET['id'])) { ?>
                <div class="rom family_member" style="padding: 35px; margin-top: -60px;">
                    <div class="row">
                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label">First Name<span class="text-danger">*</span></label>
                                <div class="col-md-12">
                                    <input type="text" name="FAMILY_FIRST_NAME[]" class="form-control" placeholder="Enter First Name">
                                </div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label">Last Name</label>
                                <div class="col-md-12">
                                    <input type="text" name="FAMILY_LAST_NAME[]" class="form-control" placeholder="Enter Last Name">
                                </div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label">Relationship</label>
                                <div class="col-md-12">
                                    <select class="form-control" name="PK_RELATIONSHIP[]">
                                        <option>Select Relationship</option>
                                        <?php
                                        $row = $db->Execute("SELECT * FROM DOA_RELATIONSHIP WHERE ACTIVE = 1");
                                        while (!$row->EOF) { ?>
                                            <option value="<?php echo $row->fields['PK_RELATIONSHIP']; ?>"><?= $row->fields['RELATIONSHIP'] ?></option>
                                        <?php $row->MoveNext();
                                        } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-2">
                            <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="$(this).closest('.row').next().slideToggle();"><i class="ti-arrow-circle-down"></i> More Info</a>
                        </div>
                        <div class="col-1">
                            <a href="javascript:;" class="btn btn-danger waves-effect waves-light text-white" style="margin-top: 30px;" onclick="removeThisFamilyMember(this);"><b><i class="ti-trash"></i></b></a>
                        </div>
                    </div>

                    <div style="display: none;">
                        <div class="row">
                            <div class="col-5">
                                <div class="form-group">
                                    <label class="form-label">Phone</label>
                                    <div class="col-md-12">
                                        <input type="text" name="FAMILY_PHONE[]" class="form-control" placeholder="Enter Phone Number">
                                    </div>
                                </div>
                            </div>
                            <div class="col-5">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <div class="col-md-12">
                                        <input type="email" name="FAMILY_EMAIL[]" class="form-control" placeholder="Enter Email Address">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Gender</label>
                                    <select class="form-control" name="FAMILY_GENDER[]">
                                        <option>Select Gender</option>
                                        <option value="Male" <?php if ($GENDER == "Male") echo 'selected = "selected"'; ?>>Male</option>
                                        <option value="Female" <?php if ($GENDER == "Female") echo 'selected = "selected"'; ?>>Female</option>
                                        <option value="Other" <?php if ($GENDER == "Other") echo 'selected = "selected"'; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="text" class="form-control datepicker-past" name="FAMILY_DOB[]">
                                </div>
                            </div>
                        </div>

                        <div class="row border-top">
                            <div class="col-2" style="margin-left: 80%">
                                <div class="form-group">
                                    <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 15px;" data-counter="<?= $family_member_count ?>" onclick="addMoreSpecialDaysFamily(this);"><i class="ti-plus"></i> New</a>
                                </div>
                            </div>
                        </div>
                        <div class="add_more_special_days">
                            <?php
                            $customer_special_date = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_SPECIAL_DATE WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                            if ($customer_special_date->RecordCount() > 0) {
                                while (!$customer_special_date->EOF) { ?>
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="form-group">
                                                <label class="form-label">Special Date</label>
                                                <div class="col-md-12">
                                                    <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="FAMILY_SPECIAL_DATE[<?= $family_member_count ?>][]" value="<?= $customer_special_date->fields['SPECIAL_DATE'] ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="form-group">
                                                <label class="form-label">Date Name</label>
                                                <div class="col-md-12">
                                                    <input type="text" class="form-control" name="FAMILY_SPECIAL_DATE_NAME[<?= $family_member_count ?>][]" value="<?= $customer_special_date->fields['DATE_NAME'] ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-2" style="padding-top: 25px;">
                                            <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                        </div>
                                    </div>
                                <?php $customer_special_date->MoveNext();
                                } ?>
                            <?php } else { ?>
                                <div class="row">
                                    <div class="col-5">
                                        <div class="form-group">
                                            <label class="form-label">Special Date</label>
                                            <div class="col-md-12">
                                                <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="FAMILY_SPECIAL_DATE[<?= $family_member_count ?>][]">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <div class="form-group">
                                            <label class="form-label">Date Name</label>
                                            <div class="col-md-12">
                                                <input type="text" class="form-control" name="FAMILY_SPECIAL_DATE_NAME[<?= $family_member_count ?>][]">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-2" style="padding-top: 25px;">
                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <div id="add_more_family_member"></div>
            <div class="form-group">
                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?= empty($_GET['id']) ? 'Continue' : 'Save' ?></button>
                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
            </div>
        </form>
    </div>

    <div class="tab-pane" id="interest" role="tabpanel">
        <form id="interest_form">
            <input type="hidden" name="FUNCTION_NAME" value="saveInterestData">
            <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
            <input type="hidden" class="PK_USER_MASTER" name="PK_USER_MASTER" value="<?= $PK_USER_MASTER ?>">
            <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?= $PK_CUSTOMER_DETAILS ?>">
            <input type="hidden" class="TYPE" name="TYPE" value="2">
            <div class="p-20">
                <div class="row">
                    <div class="col-12 mb-3 pb-3 border-bottom">
                        <label class="form-label">Interests</label>
                        <div class="col-md-12" style="margin-bottom: 0px;">
                            <div class="row">
                                <?php
                                //$PK_USER = empty($_GET['id'])?0:$_GET['id'];
                                $user_interest = $db_account->Execute("SELECT PK_INTERESTS FROM `DOA_CUSTOMER_INTEREST` WHERE `PK_USER_MASTER` = '$PK_USER_MASTER'");
                                $user_interest_array = [];
                                if ($user_interest->RecordCount() > 0) {
                                    while (!$user_interest->EOF) {
                                        $user_interest_array[] = $user_interest->fields['PK_INTERESTS'];
                                        $user_interest->MoveNext();
                                    }
                                }
                                $account_business_type = $db->Execute("SELECT PK_BUSINESS_TYPE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                $row = $db->Execute("SELECT * FROM DOA_INTERESTS WHERE ACTIVE = 1 AND PK_BUSINESS_TYPE = " . $account_business_type->fields['PK_BUSINESS_TYPE']);
                                while (!$row->EOF) { ?>
                                    <div class="col-3 mt-3">
                                        <label><input type="checkbox" name="PK_INTERESTS[]" value="<?php echo $row->fields['PK_INTERESTS']; ?>" <?= (in_array($row->fields['PK_INTERESTS'], $user_interest_array)) ? 'checked' : '' ?>> <?= $row->fields['INTERESTS'] ?></label>
                                    </div>
                                <?php $row->MoveNext();
                                } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">What promoted you to inquire with us ?</label>
                            <div class="col-md-12">
                                <input type="text" class="form-control" name="WHAT_PROMPTED_YOU_TO_INQUIRE" value="<?= $WHAT_PROMPTED_YOU_TO_INQUIRE ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">How will you grade your present skills ?</label>
                            <div class="col-md-12">
                                <select class="form-control" name="PK_SKILL_LEVEL">
                                    <option value="">Select</option>
                                    <?php
                                    $row = $db->Execute("SELECT * FROM DOA_SKILL_LEVEL WHERE ACTIVE = 1");
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_SKILL_LEVEL']; ?>" <?= ($row->fields['PK_SKILL_LEVEL'] == $PK_SKILL_LEVEL) ? 'selected' : '' ?>><?= $row->fields['SKILL_LEVEL'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Inquiry Method</label>
                            <div class="col-md-12">
                                <select class="form-control" name="PK_INQUIRY_METHOD">
                                    <option value="">Select</option>
                                    <?php
                                    $row = $db_account->Execute("SELECT * FROM DOA_INQUIRY_METHOD WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_INQUIRY_METHOD']; ?>" <?= ($row->fields['PK_INQUIRY_METHOD'] == $PK_INQUIRY_METHOD) ? 'selected' : '' ?>><?= $row->fields['INQUIRY_METHOD'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Inquiry Taker</label>
                            <div class="col-md-12">
                                <select class="form-control" name="INQUIRY_TAKER_ID">
                                    <option>Select</option>
                                    <?php
                                    $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_ROLES.PK_ROLES IN(2,3,5) AND DOA_USERS.ACTIVE AND PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_USER']; ?>" <?= ($row->fields['PK_USER'] == $INQUIRY_TAKER_ID) ? 'selected' : '' ?>><?= $row->fields['NAME'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?= empty($_GET['id']) ? 'Continue' : 'Save' ?></button>
                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
            </div>
        </form>
    </div>

    <div class="tab-pane" id="document" role="tabpanel">
        <div class="card-body m-t-10" id="agreement_document">

        </div>
        <form id="document_form">
            <input type="hidden" name="FUNCTION_NAME" value="saveDocumentData">
            <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
            <input type="hidden" class="PK_USER_MASTER" name="PK_USER_MASTER" value="<?= $PK_USER_MASTER ?>">
            <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?= $PK_CUSTOMER_DETAILS ?>">
            <input type="hidden" class="TYPE" name="TYPE" value="2">
            <div>
                <div class="card-body" id="append_user_document">
                    <?php
                    if (!empty($_GET['id'])) {
                        $user_doc_count = 0;
                        $row = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_DOCUMENT WHERE PK_USER_MASTER = '$PK_USER_MASTER'");
                        while (!$row->EOF) { ?>
                            <div class="row">
                                <div class="col-5">
                                    <div class="form-group">
                                        <label class="form-label">Document Name</label>
                                        <input type="text" name="DOCUMENT_NAME[]" class="form-control" placeholder="Enter Document Name" value="<?= $row->fields['DOCUMENT_NAME'] ?>">
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="form-group">
                                        <label class="form-label">Document File</label>
                                        <input type="file" name="FILE_PATH[]" class="form-control">
                                        <a target="_blank" href="<?= $row->fields['FILE_PATH'] ?>">View</a>
                                        <input type="hidden" name="FILE_PATH_URL[]" value="<?= $row->fields['FILE_PATH'] ?>">
                                    </div>
                                </div>
                                <div class="col-2">
                                    <div class="form-group" style="margin-top: 30px;">
                                        <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeUserDocument(this);"><i class="ti-trash"></i></a>
                                    </div>
                                </div>
                            </div>
                        <?php $row->MoveNext();
                            $user_doc_count++;
                        } ?>
                    <?php } else {
                        $user_doc_count = 1; ?>
                        <div class="row">
                            <div class="col-5">
                                <div class="form-group">
                                    <label class="form-label">Document Name</label>
                                    <input type="text" name="DOCUMENT_NAME[]" class="form-control" placeholder="Enter Document Name">
                                </div>
                            </div>
                            <div class="col-5">
                                <div class="form-group">
                                    <label class="form-label">Document File</label>
                                    <input type="file" name="FILE_PATH[]" class="form-control">
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-group" style="margin-top: 30px;">
                                    <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeUserDocument(this);"><i class="ti-trash"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="row">
                <div class="col-11">
                    <div class="form-group">
                        <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="addMoreUserDocument();"><i class="ti-plus"></i> New</a>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?= empty($_GET['id']) ? 'Continue' : 'Save' ?></button>
                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
            </div>
        </form>
    </div>

    <div class="tab-pane" id="enrollment" role="tabpanel" style="overflow-x: scroll;">
        <div id="enrollment_list" class="p-20" style="min-width: 1000px;">

        </div>
    </div>

    <div class="tab-pane" id="appointment_view" role="tabpanel">
        <div id="appointment_list_calendar" style="overflow-x: scroll;">

        </div>
    </div>

    <div class="tab-pane" id="billing" role="tabpanel">
        <div id="billing_list" class="p-20">

        </div>
    </div>

    <div class="tab-pane" id="comments" role="tabpanel">
        <div class="p-20">
            <a class="btn btn-info d-none d-lg-block m-15 text-white" href="javascript:;" onclick="createUserComment();" style="width: 120px; float: right;"><i class="fa fa-plus-circle"></i> Create New</a>
            <table id="myTable" class="table table-striped border">
                <thead>
                    <tr>
                        <th>Commented Date</th>
                        <th>Commented User</th>
                        <th>Comment</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $comment_data = $db->Execute("SELECT $account_database.DOA_COMMENT.PK_COMMENT, $account_database.DOA_COMMENT.COMMENT, $account_database.DOA_COMMENT.COMMENT_DATE, $account_database.DOA_COMMENT.ACTIVE, CONCAT($master_database.DOA_USERS.FIRST_NAME, ' ', $master_database.DOA_USERS.LAST_NAME) AS FULL_NAME FROM $account_database.`DOA_COMMENT` INNER JOIN $master_database.DOA_USERS ON $account_database.DOA_COMMENT.BY_PK_USER = $master_database.DOA_USERS.PK_USER WHERE $account_database.DOA_COMMENT.`FOR_PK_USER` = " . $PK_USER);
                    $i = 1;
                    while (!$comment_data->EOF) { ?>
                        <tr>
                            <td onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><?= date('m/d/Y', strtotime($comment_data->fields['COMMENT_DATE'])) ?></td>
                            <td onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><?= $comment_data->fields['FULL_NAME'] ?></td>
                            <td onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><?= $comment_data->fields['COMMENT'] ?></td>
                            <td>
                                <a href="javascript:;" onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><i class="ti-pencil" style="font-size: 22px;"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                <a href="javascript:;" onclick='javascript:deleteComment(<?= $comment_data->fields['PK_COMMENT'] ?>);return false;'><i class="ti-trash" style="font-size: 22px;"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                <?php if ($comment_data->fields['ACTIVE'] == 1) { ?>
                                    <span class="active-box-green"></span>
                                <?php } else { ?>
                                    <span class="active-box-red"></span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php $comment_data->MoveNext();
                        $i++;
                    } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane" id="payment_due" role="tabpanel">
        <div id="payment_due_list" class="p-20">

        </div>
    </div>



    <!--Edit Billing Due Date Model-->
    <div class="modal fade" id="billing_due_date_model" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="edit_due_date_form" method="post">
                <input type="hidden" name="PK_ENROLLMENT_LEDGER" id="PK_ENROLLMENT_LEDGER">
                <input type="hidden" name="old_due_date" id="old_due_date">
                <input type="hidden" name="edit_type" id="edit_type">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4><b>Edit Due Date</b></h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">Due Date</label>
                                    <input type="text" id="due_date" name="due_date" class="form-control datepicker-normal" placeholder="Due Date" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">Enter your profile password</label>
                                    <input type="password" id="due_date_verify_password" name="due_date_verify_password" class="form-control" placeholder="Password" required>
                                    <p id="due_date_verify_password_error" style="color: red;"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="card-button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Process</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!--Comment Model-->
    <div class="modal fade" id="commentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><b id="comment_header">Add Comment</b></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="$('#commentModal').modal('hide');"></button>
                </div>
                <form id="comment_add_edit_form" role="form" action="" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="FUNCTION_NAME" value="saveCommentData">
                        <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                        <input type="hidden" name="PK_COMMENT" id="PK_COMMENT" value="0">
                        <div class="p-20">
                            <div class="form-group">
                                <label class="form-label">Comments</label>
                                <textarea class="form-control" rows="10" name="COMMENT" id="COMMENT" required></textarea>
                            </div>
                            <div class="form-group" id="comment_active" style="display: none;">
                                <label class="form-label">Active</label>
                                <div>
                                    <label><input type="radio" id="COMMENT_ACTIVE_1" name="ACTIVE" value="1">&nbsp;&nbsp;&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label><input type="radio" id="COMMENT_ACTIVE_0" name="ACTIVE" value="0">&nbsp;&nbsp;&nbsp;No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .progress-bar {
        border-radius: 5px;
        height: 18px !important;
    }
</style>

<script>
    $('.datepicker-normal-calendar').datepicker({
        format: 'mm/dd/yyyy',
    });
    $('.timepicker-normal').timepicker({
        timeFormat: 'hh:mm p',
        interval: 15,
    });
</script>

<script>
    var PK_USER = parseInt(<?= empty($PK_USER) ? 0 : $PK_USER ?>);

    function createUserComment() {
        $('#comment_header').text("Add Comment");
        $('#PK_COMMENT').val(0);
        $('#COMMENT').val('');
        $('#COMMENT_DATE').val('');
        $('#comment_active').hide();
        openCommentModel();
    }

    function editComment(PK_COMMENT) {
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            dataType: 'JSON',
            data: {
                FUNCTION_NAME: 'getEditCommentData',
                PK_COMMENT: PK_COMMENT
            },
            success: function(data) {
                $('#comment_header').text("Edit Comment");
                $('#PK_COMMENT').val(data.fields.PK_COMMENT);
                $('#COMMENT').val(data.fields.COMMENT);
                $('#COMMENT_DATE').val(data.fields.COMMENT_DATE);
                $('#COMMENT_ACTIVE_' + data.fields.ACTIVE).prop('checked', true);
                $('#comment_active').show();
                openCommentModel();
            }
        });
    }

    function openCommentModel() {
        $('#commentModal').modal('show');
    }

    $(document).on('submit', '#comment_add_edit_form', function(event) {
        event.preventDefault();
        let form_data = new FormData($('#comment_add_edit_form')[0]); //$('#document_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            processData: false,
            contentType: false,
            success: function(data) {
                window.location.href = 'all_schedules.php?view=table';
            }
        });
    });

    function deleteComment(PK_COMMENT) {
        let conf = confirm("Are you sure you want to delete?");
        if (conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'deleteCommentData',
                    PK_COMMENT: PK_COMMENT
                },
                success: function(data) {
                    window.location.href = 'all_schedules.php?view=table';
                }
            });
        }
    }

    $('.multi_sumo_select').SumoSelect({
        placeholder: 'Select Location',
        selectAll: true
    });

    $(document).ready(function() {
        let tab_link = <?= empty($_GET['tab']) ? 0 : $_GET['tab'] ?>;
        fetch_state(<?php echo $PK_COUNTRY; ?>);
        if (tab_link.id == 'profile') {
            $('#profile_tab_link')[0].click();
        }
        if (tab_link.id == 'appointment') {
            $('#appointment_tab_link')[0].click();
        }
        if (tab_link.id == 'billing') {
            $('#billing_tab_link')[0].click();
        }
        if (tab_link.id == 'comments') {
            $('#comment_tab_link')[0].click();
        }
        let on_tab_link = <?= empty($_GET['on_tab']) ? 0 : $_GET['on_tab'] ?>;
        if (on_tab_link.id == 'comments') {
            $('#comment_tab_link')[0].click();
        }
    });

    function fetch_state(PK_COUNTRY) {
        jQuery(document).ready(function() {
            let data = "PK_COUNTRY=" + PK_COUNTRY + "&PK_STATES=<?= $PK_STATES; ?>";
            let value = $.ajax({
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
</script>
<script>
    function isGood(password) {
        let password_strength = document.getElementById("password-text");

        if (password.length == 0) {
            password_strength.innerHTML = "";
            return;
        }
        //Regular Expressions.
        let regex = new Array();
        regex.push("[A-Z]"); //Uppercase Alphabet.
        regex.push("[a-z]"); //Lowercase Alphabet.
        regex.push("[0-9]"); //Digit.
        regex.push("[$@$!%*#?&]"); //Special Character.
        let passed = 0;
        //Validate for each Regular Expression.
        for (let i = 0; i < regex.length; i++) {
            if (new RegExp(regex[i]).test(password)) {
                passed++;
            }
        }
        //Display status.
        let strength = "";
        switch (passed) {
            case 0:
            case 1:
            case 2:
                strength = "<small class='progress-bar bg-danger' style='width: 50%'>Weak</small>";
                break;
            case 3:
                strength = "<small class='progress-bar bg-warning' style='width: 60%'>Medium</small>";
                break;
            case 4:
                strength = "<small class='progress-bar bg-success' style='width: 100%'>Strong</small>";
                break;

        }
        // alert(strength);
        password_strength.innerHTML = strength;
    }

    function ValidateUsername() {
        let username = document.getElementById("User_Id").value;
        let lblError = document.getElementById("lblError");
        lblError.innerHTML = "";
        let expr = /^[a-zA-Z0-9_]{8,20}$/;
        if (!expr.test(username)) {
            lblError.innerHTML = "Only Alphabets, Numbers and Underscore and between 8 to 20 characters.";
        } else {
            lblError.innerHTML = "";
        }
    }

    $(document).on('click', '#cancel_button', function() {
        window.location.href = 'all_customers.php';
    });

    $(document).on('change', '.engagement_terms', function() {
        if ($(this).is(':checked')) {
            $(this).closest('.col-1').next().slideDown();
        } else {
            $(this).closest('.col-1').next().slideUp();
        }
    });

    function createLogin(param) {
        if ($(param).is(':checked')) {
            $('#login_info_tab').show();
            $('#phone_label').text('*');
            $('#PHONE').prop('required', true);
            $('#email_label').text('*');
            $('#EMAIL_ID').prop('required', true);
            $('#submit_button').hide();
            $('#next_button_interest').hide();
            $('#next_button').show();
        } else {
            $('#login_info_tab').hide();
            $('#phone_label').text('');
            $('#PHONE').prop('required', false);
            $('#email_label').text('');
            $('#EMAIL_ID').prop('required', false);
            $('#submit_button').show();
            $('#next_button_interest').show();
            $('#next_button').hide();
        }
    }

    let counter = parseInt(<?= $user_doc_count ?>);

    function addMoreUserDocument() {
        $('#append_user_document').append(`<div class="row">
                                                <div class="col-5">
                                                    <div class="form-group">
                                                        <label class="form-label">Document Name</label>
                                                        <input type="text" name="DOCUMENT_NAME[]" class="form-control" placeholder="Enter Document Name">
                                                    </div>
                                                </div>
                                                <div class="col-5">
                                                    <div class="form-group">
                                                        <label class="form-label">Document File</label>
                                                        <input type="file" name="FILE_PATH[]" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <div class="form-group" style="margin-top: 30px;">
                                                        <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeUserDocument(this);"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>
                                              </div>`);
        counter++;
    }

    function removeUserDocument(param) {
        $(param).closest('.row').remove();
        counter--;
    }

    function goLoginInfo() {
        let element = $('#profile').find('input');
        let count = element.length;
        element.each(function() {
            if ($(this).prop('required') && ($(this).val() === '')) {
                $(this).focus();
                return false;
            }
            count--;
            if (count === 0) {
                $('#login_info_tab_link')[0].click();
            }
        });
    }

    function goInterest() {
        let element = $('#profile').find('input');
        let count = element.length;
        element.each(function() {
            if ($(this).prop('required') && ($(this).val() === '')) {
                $(this).focus();
                return false;
            }
            count--;
            if (count === 0) {
                $('#interest_tab_link')[0].click();
            }
        });
    }

    function removeThis(param) {
        $(param).closest('.row').remove();
    }

    function addMorePhone() {
        $('#add_more_phone').append(`<div class="row">
                                            <div class="col-9">
                                                <div class="form-group">
                                                    <label class="form-label">Phone</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="CUSTOMER_PHONE[]" class="form-control" placeholder="Enter Phone Number">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-2" style="padding-top: 25px;">
                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                            </div>
                                        </div>`);
    }

    function addMoreEmail() {
        $('#add_more_email').append(`<div class="row">
                                            <div class="col-9">
                                                <div class="form-group">
                                                    <label class="col-md-12">Email</label>
                                                    <div class="col-md-12">
                                                        <input type="email" name="CUSTOMER_EMAIL[]" class="form-control" placeholder="Enter Email Address">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-2" style="padding-top: 25px;">
                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                            </div>
                                         </div>`);
    }

    function addMoreSpecialDays(param) {
        $(param).closest('.row').next('.add_more_special_days').append(`<div class="row">
                                                    <div class="col-5">
                                                        <div class="form-group">
                                                            <label class="form-label">Special Date</label>
                                                            <div class="col-md-12">
                                                                <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="CUSTOMER_SPECIAL_DATE[]">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-5">
                                                        <div class="form-group">
                                                            <label class="form-label">Date Name</label>
                                                            <div class="col-md-12">
                                                                <input type="text" class="form-control" name="CUSTOMER_SPECIAL_DATE_NAME[]">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-2" style="padding-top: 25px;">
                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>`);
    }

    let family_special_day_count = parseInt(<?= ($family_member_count == 0) ? 0 : ($family_member_count - 1) ?>);

    function addMoreFamilyMember() {
        family_special_day_count++;
        $('#add_more_family_member').append(`<div class="row family_member" style="padding: 35px; margin-top: -60px;"">
                                                    <div class="row">
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <label class="form-label">First Name<span class="text-danger">*</span></label>
                                                                <div class="col-md-12">
                                                                    <input type="text" name="FAMILY_FIRST_NAME[]" class="form-control" placeholder="Enter First Name">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <label class="form-label">Last Name</label>
                                                                <div class="col-md-12">
                                                                    <input type="text" name="FAMILY_LAST_NAME[]" class="form-control" placeholder="Enter Last Name">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <label class="form-label">Relationship</label>
                                                                <div class="col-md-12">
                                                                    <select class="form-control" name="PK_RELATIONSHIP[]">
                                                                        <option>Select Relationship</option>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT * FROM DOA_RELATIONSHIP WHERE ACTIVE = 1");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_RELATIONSHIP']; ?>"><?= $row->fields['RELATIONSHIP'] ?></option>
                                                                        <?php $row->MoveNext();
                                                                        } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="$(this).closest('.row').next().slideToggle();"><i class="ti-arrow-circle-down"></i> More Info</a>
                                                        </div>
                                                        <div class="col-1">
                                                            <a href="javascript:;" class="btn btn-danger waves-effect waves-light text-white" style="margin-top: 30px;" onclick="removeThisFamilyMember(this);"><b><i class="ti-trash"></i></b></a>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <div class="form-group">
                                                                    <label class="form-label">Phone</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="FAMILY_PHONE[]" class="form-control" placeholder="Enter Phone Number">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-5">
                                                                <div class="form-group">
                                                                    <label class="col-md-12">Email</label>
                                                                    <div class="col-md-12">
                                                                        <input type="email" name="FAMILY_EMAIL[]" class="form-control" placeholder="Enter Email Address">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Gender</label>
                                                                    <select class="form-control" name="FAMILY_GENDER[]">
                                                                        <option>Select Gender</option>
                                                                        <option value="Male" <?php if ($GENDER == "Male") echo 'selected = "selected"'; ?>>Male</option>
                                                                        <option value="Female" <?php if ($GENDER == "Female") echo 'selected = "selected"'; ?>>Female</option>
                                                                        <option value="Other" <?php if ($GENDER == "Other") echo 'selected = "selected"'; ?>>Other</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Date of Birth</label>
                                                                    <input type="text" class="form-control datepicker-past" name="FAMILY_DOB[]">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-2" style="margin-left: 80%">
                                                                <div class="form-group">
                                                                    <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 15px;" data-counter="${family_special_day_count}" onclick="addMoreSpecialDaysFamily(this);"><i class="ti-plus"></i> New</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="add_more_special_days">
                                                            <div class="row">
                                                                <div class="col-5">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Special Date</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="FAMILY_SPECIAL_DATE[${family_special_day_count}][]">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-5">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Date Name</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" class="form-control" name="FAMILY_SPECIAL_DATE_NAME[${family_special_day_count}][]">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-2" style="padding-top: 25px;">
                                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>`);
    }


    function addMoreSpecialDaysFamily(param) {
        let data_counter = $(param).data('counter');
        $(param).closest('.row').next('.add_more_special_days').append(`<div class="row">
                                                                                <div class="col-5">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Special Date</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="FAMILY_SPECIAL_DATE[${data_counter}][]">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-5">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Date Name</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" class="form-control" name="FAMILY_SPECIAL_DATE_NAME[${data_counter}][]">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-2" style="padding-top: 25px;">
                                                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                                </div>
                                                                            </div>`);
    }

    function removeThisFamilyMember(param) {
        family_special_day_count--;
        $(param).closest('.family_member').remove();
    }

    $(document).on('submit', '#profile_form', function(event) {
        event.preventDefault();
        let form_data = new FormData($('#profile_form')[0]); //$('#profile_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            processData: false,
            contentType: false,
            dataType: 'JSON',
            success: function(data) {
                console.log(data);
                $('.PK_USER').val(data.PK_USER);
                $('.PK_USER_MASTER').val(data.PK_USER_MASTER);
                $('.PK_CUSTOMER_DETAILS').val(data.PK_CUSTOMER_DETAILS);
                if (PK_USER == 0) {
                    if ($('#CREATE_LOGIN').is(':checked')) {
                        $('#login_info_tab_link')[0].click();
                    } else {
                        $('#family_tab_link')[0].click();
                    }
                } else {
                    window.location.href = 'all_schedules.php?view=table';
                }
            }
        });
    });

    $(document).on('submit', '#login_form', function(event) {
        event.preventDefault();
        let PASSWORD = $('#PASSWORD').val();
        let CONFIRM_PASSWORD = $('#CONFIRM_PASSWORD').val();
        if (PASSWORD === CONFIRM_PASSWORD) {
            let SAVED_OLD_PASSWORD = $('#SAVED_OLD_PASSWORD').val();
            let OLD_PASSWORD = $('#OLD_PASSWORD').val();
            if (SAVED_OLD_PASSWORD) {
                $.ajax({
                    url: "ajax/check_old_password.php",
                    type: 'POST',
                    data: {
                        ENTERED_PASSWORD: OLD_PASSWORD,
                        SAVED_PASSWORD: SAVED_OLD_PASSWORD
                    },
                    success: function(data) {
                        if (data == 0) {
                            $('#password_error').text('Old Password not matched');
                        } else {
                            let form_data = $('#login_form').serialize();
                            $.ajax({
                                url: "ajax/AjaxFunctions.php",
                                type: 'POST',
                                data: form_data,
                                success: function(data) {
                                    $('.PK_USER').val(data);
                                    if (PK_USER == 0) {
                                        $('#family_tab_link')[0].click();
                                    } else {
                                        window.location.href = 'all_schedules.php?view=table';
                                    }
                                }
                            });
                        }
                    }
                });
            } else {
                let form_data = $('#login_form').serialize();
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: form_data,
                    success: function(data) {
                        $('.PK_USER').val(data);
                        if (PK_USER == 0) {
                            $('#family_tab_link')[0].click();
                        } else {
                            window.location.href = 'all_schedules.php?view=table';
                        }
                    }
                });
            }
        } else {
            $('#password_error').text('Password and Confirm Password not matched');
        }
    });

    $(document).on('submit', '#family_form', function(event) {
        event.preventDefault();
        let form_data = $('#family_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            success: function(data) {
                if (PK_USER == 0) {
                    $('#interest_tab_link')[0].click();
                } else {
                    window.location.href = 'all_schedules.php?view=table';
                }
            }
        });
    });

    $(document).on('submit', '#interest_form', function(event) {
        event.preventDefault();
        let form_data = $('#interest_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            success: function(data) {
                if (PK_USER == 0) {
                    $('#document_tab_link')[0].click();
                } else {
                    window.location.href = 'all_schedules.php?view=table';
                }
            }
        });
    });

    $(document).on('submit', '#document_form', function(event) {
        event.preventDefault();
        let form_data = new FormData($('#document_form')[0]); //$('#document_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            processData: false,
            contentType: false,
            success: function(data) {
                window.location.href = 'all_schedules.php?view=table';
            }
        });
    });

    function openReceipt(PK_ENROLLMENT_MASTER, RECEIPT_NUMBER) {
        let RECEIPT_NUMBER_ARRAY = RECEIPT_NUMBER.split(',');
        for (let i = 0; i < RECEIPT_NUMBER_ARRAY.length; i++) {
            window.open('generate_receipt_pdf.php?master_id=' + PK_ENROLLMENT_MASTER + '&receipt=' + RECEIPT_NUMBER_ARRAY[i], '_blank');
        }
    }
</script>

<script>
    $('#NAME').SumoSelect({
        placeholder: 'Select Customer',
        search: true,
        searchText: 'Search...'
    });

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    $('.datepicker-past').datepicker({
        format: 'mm/dd/yyyy',
        maxDate: 0,
        changeMonth: true,
        changeYear: true,
        yearRange: '1900:' + new Date().getFullYear(),
    });

    function confirmComplete(param) {
        let conf = confirm("Do you want to mark this appointment as completed?");
        if (conf) {
            let PK_APPOINTMENT_MASTER = $(param).data('id');
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'markAppointmentCompleted',
                    PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                },
                success: function(data) {
                    if (data == 1) {
                        $(param).closest('td').html('<span class="status-box" style="background-color: #ff0019">Completed</span>');
                    } else {
                        alert("Something wrong");
                    }
                }
            });
        }
    }

    var enr_tab_type = '';
    var page_count = 1;
    var loading = false;
    var hasMore = true;
    var observer;

    function showEnrollmentList(page, type) {
        enr_tab_type = type;
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
        let PK_USER = $('.PK_USER').val();

        loading = true;
        $("#load-marker").text("Loading...");

        $.ajax({
            url: "pagination/enrollment.php",
            type: "GET",
            data: {
                search_text: '',
                page: page,
                type: type,
                pk_user: PK_USER,
                master_id: PK_USER_MASTER
            },
            cache: false,
            success: function(result) {
                if (result && result.trim() !== "") {
                    // Insert new content ABOVE the marker
                    $('#load-marker').before(result);
                    loading = false;
                } else {
                    // No more data
                    hasMore = false;
                    $("#load-marker").text("No more data");
                    if (observer) observer.disconnect();
                }
            },
            error: function() {
                loading = false;
                $("#load-marker").text("Error loading data");
            }
        });
    }

    // Setup observer only once
    function enrollmentLoadMore(type) {
        enr_tab_type = type;
        page_count = 1;
        hasMore = true;
        loading = false;
        $("#enrollment_list").html('<div id="load-marker" style="text-align:center; padding:10px;">Loading <i class="fas fa-spinner fa-pulse" style="font-size: 15px;"></i></div>');

        // Load first page
        showEnrollmentList(page_count, enr_tab_type);

        if (observer) observer.disconnect();

        observer = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting && !loading && hasMore) {
                page_count++;
                showEnrollmentList(page_count, enr_tab_type);
            }
        }, {
            rootMargin: "300px",
            threshold: 0.1
        });

        observer.observe(document.querySelector("#load-marker"));
    }

    $(window).on("scroll", function() {
        if (!loading && hasMore && enr_tab_type != '') {
            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 200) {
                page_count++;
                showEnrollmentList(page_count, enr_tab_type);
            }
        }
    });

    function showAgreementDocument() {
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
        $.ajax({
            url: "pagination/agreement_document.php",
            type: "GET",
            data: {
                master_id: PK_USER_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#agreement_document').html(result);
            }
        });
        window.scrollTo(0, 0);
    }

    function showAppointmentListView(page) {
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
        $.ajax({
            url: "pagination/appointment.php",
            type: "GET",
            data: {
                search_text: '',
                page: page,
                master_id: PK_USER_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#appointment_list_calendar').html(result)
            }
        });
        window.scrollTo(0, 0);
    }

    function editpage(param) {
        var id = $(param).val();
        var master_id = $(param).find(':selected').data('master_id');
        window.location.href = "customer.php?id=" + id + "&master_id=" + master_id;

    }
</script>

<script>
    $('#verify_password_form').on('submit', function(event) {
        event.preventDefault();
        let pk_user = $('.PK_USER').val();
        let password = $('#verify_password').val();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: {
                FUNCTION_NAME: 'deleteCustomerAfterVerify',
                pk_user: pk_user,
                PASSWORD: password
            },
            success: function(data) {
                $('#verify_password_error').slideUp();
                if (data == 1) {
                    Swal.fire({
                        title: "Deleted!",
                        text: "Your file has been deleted.",
                        icon: "success",
                        timer: 3000,
                    }).then((result) => {
                        window.location.href = 'all_customers.php';
                    });
                } else {
                    $('#verify_password_error').text("Incorrect Password").slideDown();
                }
            }
        });
    });

    $('#edit_due_date_form').on('submit', function(event) {
        event.preventDefault();

        let PK_ENROLLMENT_LEDGER = $('#PK_ENROLLMENT_LEDGER').val();
        let old_due_date = $('#old_due_date').val();
        let due_date = $('#due_date').val();
        let edit_type = $('#edit_type').val();
        let due_date_verify_password = $('#due_date_verify_password').val();

        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: {
                FUNCTION_NAME: 'updateBillingDueDate',
                PK_ENROLLMENT_LEDGER: PK_ENROLLMENT_LEDGER,
                old_due_date: old_due_date,
                due_date: due_date,
                edit_type: edit_type,
                due_date_verify_password: due_date_verify_password
            },
            success: function(data) {
                $('#due_date_verify_password_error').slideUp();
                if (data == 1) {
                    Swal.fire({
                        title: "Updated!",
                        text: "Due Date is Updated.",
                        icon: "success",
                        timer: 3000,
                    }).then((result) => {
                        $('#billing_due_date_model').modal('hide');
                        enrollmentLoadMore('normal');
                        getPaymentDueList();
                    });
                } else {
                    $('#due_date_verify_password_error').text("Incorrect Password").slideDown();
                }
            }
        });
    });
</script>

<script>
    function changeServiceProvider() {
        $('#change_service_provider').hide();
        $('#cancel_change_service_provider').show();
        $('#service_provider_select').slideDown();
        $('#service_provider_name').slideUp();
        $('#date_time_div').slideUp();
        $('#schedule_div').slideDown();
    }

    function cancelChangeServiceProvider() {
        $('#change_service_provider').show();
        $('#cancel_change_service_provider').hide();
        $('#service_provider_select').slideUp();
        $('#service_provider_name').slideDown();
        $('#date_time_div').slideDown();
        $('#schedule_div').slideUp();
    }

    function changeEnrollment() {
        $('#change_enrollment').hide();
        $('#cancel_change_enrollment').show();
        $('#enrollment_select').show();
        $('.enrollment_info').hide();
        $('#enrollment_div').removeClass('col-4').addClass('col-12');
        changeSchedulingCode();
    }

    function cancelChangeEnrollment() {
        $('#change_enrollment').show();
        $('#cancel_change_enrollment').hide();
        $('#enrollment_select').hide();
        $('.enrollment_info').show();
        $('#enrollment_div').removeClass('col-12').addClass('col-4');
    }

    function changeSchedulingCode() {
        $('#change_scheduling_code').hide();
        $('#cancel_change_scheduling_code').show();
        $('#scheduling_code_select').slideDown();
        $('#scheduling_code_name').slideUp();
        $('#date_time_div').slideUp();
        $('#schedule_div').slideDown();
    }

    function cancelChangeSchedulingCode() {
        $('#change_scheduling_code').show();
        $('#cancel_change_scheduling_code').hide();
        $('#scheduling_code_select').slideUp();
        $('#scheduling_code_name').slideDown();
        $('#date_time_div').slideDown();
        $('#schedule_div').slideUp();
    }

    function reschedule() {
        $('#cancel_reschedule').show();
        $('#date_time_div').slideUp();
        $('#schedule_div').slideDown();
    }

    function cancelReschedule() {
        $('#cancel_reschedule').hide();
        $('#date_time_div').slideDown();
        $('#schedule_div').slideUp();
    }

    function changeStatus() {
        $('#cancel_change_status').show();
        $('#change_status').hide();
        $('#PK_APPOINTMENT_STATUS').slideDown().show();
        $('#appointment_status').slideUp();
    }

    function cancelChangeStatus() {
        $('#cancel_change_status').hide();
        $('#change_status').show();
        $('#PK_APPOINTMENT_STATUS').slideUp();
        $('#appointment_status').slideDown();
    }

    function cancelAppointment() {
        let text = "Did you really want to confirm Appointment?";
        if (confirm(text) == true) {
            let PK_APPOINTMENT_MASTER = $('.PK_APPOINTMENT_MASTER').val();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {
                    FUNCTION_NAME: 'cancelAppointment',
                    PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                },
                async: false,
                cache: false,
                success: function(result) {
                    window.location.href = 'all_schedules.php';
                }
            });
        }
    }
</script>

<script>
    function changeServiceProvider() {
        $('#change_service_provider').hide();
        $('#cancel_change_service_provider').show();
        $('#service_provider_select').slideDown();
        $('#service_provider_name').slideUp();
        $('#date_time_div').slideUp();
        $('#schedule_div').slideDown();
    }

    function cancelChangeServiceProvider() {
        $('#change_service_provider').show();
        $('#cancel_change_service_provider').hide();
        $('#service_provider_select').slideUp();
        $('#service_provider_name').slideDown();
        $('#date_time_div').slideDown();
        $('#schedule_div').slideUp();
    }

    function reschedule() {
        $('#cancel_reschedule').show();
        $('#date_time_div').slideUp();
        $('#schedule_div').slideDown();
    }

    function cancelReschedule() {
        $('#cancel_reschedule').hide();
        $('#date_time_div').slideDown();
        $('#schedule_div').slideUp();
    }

    function changeStatus() {
        $('#cancel_change_status').show();
        $('#change_status').hide();
        $('#PK_APPOINTMENT_STATUS').slideDown();
        $('#appointment_status').slideUp();
    }

    function cancelChangeStatus() {
        $('#cancel_change_status').hide();
        $('#change_status').show();
        $('#PK_APPOINTMENT_STATUS').slideUp();
        $('#appointment_status').slideDown();
    }

    function changeAppointmentStatus(param) {
        if ($(param).val() == 4) {
            $('#no_show_div').slideDown();
        } else {
            $('#no_show_div').slideUp();
        }

        if ($(param).val() == 2) {
            $('#IS_CHARGED').val(1);
        } else {
            $('#IS_CHARGED').val(0);
        }
    }

    function deleteThisAppointment(PK_APPOINTMENT_MASTER) {
        Swal.fire({
            title: "Are you sure?",
            text: "Deleting this Appointment will not revert back.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {
                        FUNCTION_NAME: 'deleteAppointment',
                        type: 'normal',
                        PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                    },
                    success: function(data) {
                        window.location.href = 'all_schedules.php';
                    }
                });
            } else {
                Swal.fire({
                    title: "Cancelled",
                    text: "Your appointment is safe :)",
                    icon: "error"
                });
            }
        });
    }

    function cancelAppointment() {
        let text = "Did you really want to confirm Appointment?";
        if (confirm(text) == true) {
            let PK_APPOINTMENT_MASTER = $('.PK_APPOINTMENT_MASTER').val();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {
                    FUNCTION_NAME: 'cancelAppointment',
                    PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                },
                async: false,
                cache: false,
                success: function(result) {
                    window.location.href = 'all_schedules.php';
                }
            });
        }
    }
</script>

<script>
    function payAll(PK_ENROLLMENT_MASTER, ENROLLMENT_ID) {
        let BILLED_AMOUNT = [];
        let PK_ENROLLMENT_LEDGER = [];

        $(".BILLED_AMOUNT:checked").each(function() {
            BILLED_AMOUNT.push($(this).val());
            PK_ENROLLMENT_LEDGER.push($(this).data('pk_enrollment_ledger'));
        });

        let TOTAL = BILLED_AMOUNT.reduce(getSum, 0);

        function getSum(total, num) {
            return total + Math.round(num);
        }

        $('#enrollment_number').text(ENROLLMENT_ID);
        $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
        $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#AMOUNT_TO_PAY_CUSTOMER').val(parseFloat(TOTAL).toFixed(2));
        $('#payment_confirmation_form_div_customer').slideDown();
        openPaymentModel();
    }
</script>

<script>
    document.getElementById('IMAGE').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            let img = document.getElementById('previewImage');
            img.src = e.target.result;
            img.classList.remove('hidden');
            document.getElementById('imageLink').addEventListener('click', function() {
                openModal(e.target.result, 'image');
            });
        };
        reader.readAsDataURL(file);
    });

    document.getElementById('VIDEO').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            let video = document.getElementById('previewVideo');
            video.src = e.target.result;
            video.classList.remove('hidden');
            document.getElementById('videoLink').addEventListener('click', function() {
                openModal(e.target.result, 'video');
            });
        };
        reader.readAsDataURL(file);
    });

    function openModal(src, type) {
        const modal = document.getElementById('modal');
        const img = document.getElementById('modalImage');
        const video = document.getElementById('modalVideo');

        if (type === 'image') {
            img.src = src;
            img.classList.remove('hidden');
            video.classList.add('hidden');
        } else {
            video.src = src;
            video.classList.remove('hidden');
            img.classList.add('hidden');
        }

        modal.classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('modal').classList.add('hidden');
    }
</script>

<!-- JavaScript for Popup -->
<script>
    function showPopup(type, src) {
        let popup = document.getElementById("mediaPopup");
        let image = document.getElementById("popupImage");
        let video = document.getElementById("popupVideo");
        let videoSource = document.getElementById("popupVideoSource");

        if (type === 'image') {
            image.src = src;
            image.style.display = "block";
            video.style.display = "none";
        } else if (type === 'video') {
            videoSource.src = src;
            video.load();
            video.style.display = "block";
            image.style.display = "none";
        }

        popup.style.display = "flex";

        // Add event listener to detect ESC key press
        document.addEventListener("keydown", escClose);
    }

    function closePopup() {
        document.getElementById("mediaPopup").style.display = "none";
        document.removeEventListener("keydown", escClose); // Remove listener when popup is closed
    }

    // Function to detect ESC key press and close the popup
    function escClose(event) {
        if (event.key === "Escape") {
            closePopup();
        }
    }
</script>

<script>
    function getPaymentDueList() {
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
        $.ajax({
            url: "pagination/payment_due.php",
            type: "GET",
            data: {
                master_id: PK_USER_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#payment_due_list').html(result);

                var table = $('#paymentDueTable').DataTable({
                    order: [
                        [1, 'desc']
                    ],
                    columnDefs: [{
                        type: 'date',
                        targets: 1
                    }],
                });
            }
        });
        window.scrollTo(0, 0);
    }
</script>