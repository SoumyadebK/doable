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

// Format date for HTML input
$date_for_html_input = date("Y-m-d", strtotime($res->fields['DATE']));
$start_time_for_input = date("H:i", strtotime($START_TIME));

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

$selected_primary_location = $db->Execute("SELECT DOA_LOCATION.LOCATION_NAME FROM DOA_USER_MASTER LEFT JOIN DOA_LOCATION ON DOA_USER_MASTER.PRIMARY_LOCATION_ID = DOA_LOCATION.PK_LOCATION WHERE PK_USER_MASTER = '$CUSTOMER_ID'");
$primary_location = $selected_primary_location->fields['LOCATION_NAME'];

if ($PK_USER_MASTER > 0) {
    makeExpiryEnrollmentComplete($PK_USER_MASTER);
    makeMiscComplete($PK_USER_MASTER);
    makeDroppedCancelled($PK_USER_MASTER);
    checkAllEnrollmentStatus($PK_USER_MASTER);
}

?>



<!-- Appointment Details -->

<div class="appointment-profile d-flex p-3 pb-0">
    <div class="d-flex align-items-center gap-3 f12 theme-text-light">
        <div class="profilename-short d-flex align-items-center justify-content-center fw-semibold"><?php
                                                                                                    // Get initials
                                                                                                    $names = explode(' ', $selected_customer);
                                                                                                    $initials = '';
                                                                                                    if (count($names) >= 2) {
                                                                                                        $initials = substr($names[0], 0, 1) . substr($names[1], 0, 1);
                                                                                                    } else {
                                                                                                        $initials = substr($selected_customer, 0, 2);
                                                                                                    }
                                                                                                    echo strtoupper($initials);
                                                                                                    ?></div>
        <div class="profilename-data">
            <h6 class="mb-1"><a href="#!" id="openDrawer3"><?php echo $selected_customer; ?></a></h6>
            <span><?php echo htmlspecialchars($primary_location ?: 'Studio Location'); ?></span>
        </div>
    </div>
    <div class="profilebtn-area ms-auto">
        <button type="button" class="btn btn-secondary gap-1 px-2 py-1 f12 theme-text-light border-1 fw-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 474 474" style="enable-background:new 0 0 474 474;" xml:space="preserve" width="12px" height="12px" fill="CurrentColor">
                <path d="M437.5,59.3h-401C16.4,59.3,0,75.7,0,95.8v282.4c0,20.1,16.4,36.5,36.5,36.5h401c20.1,0,36.5-16.4,36.5-36.5V95.8 C474,75.7,457.6,59.3,437.5,59.3z M432.2,86.3L239.5,251.1L46.8,86.3H432.2z M447,378.2c0,5.2-4.3,9.5-9.5,9.5h-401 c-5.2,0-9.5-4.3-9.5-9.5V104.9l203.7,174.2c0.1,0.1,0.3,0.2,0.4,0.3c0.1,0.1,0.3,0.2,0.4,0.3c0.3,0.2,0.5,0.4,0.8,0.5 c0.1,0.1,0.2,0.1,0.3,0.2c0.4,0.2,0.8,0.4,1.2,0.6c0.1,0,0.2,0.1,0.3,0.1c0.3,0.1,0.6,0.3,1,0.4c0.1,0,0.3,0.1,0.4,0.1 c0.3,0.1,0.6,0.2,0.9,0.2c0.1,0,0.3,0.1,0.4,0.1c0.3,0.1,0.7,0.1,1,0.2c0.1,0,0.2,0,0.3,0c0.4,0,0.9,0.1,1.3,0.1l0,0l0,0 c0.4,0,0.9,0,1.3-0.1c0.1,0,0.2,0,0.3,0c0.3,0,0.7-0.1,1-0.2c0.1,0,0.3-0.1,0.4-0.1c0.3-0.1,0.6-0.2,0.9-0.2c0.1,0,0.3-0.1,0.4-0.1 c0.3-0.1,0.6-0.2,1-0.4c0.1,0,0.2-0.1,0.3-0.1c0.4-0.2,0.8-0.4,1.2-0.6c0.1-0.1,0.2-0.1,0.3-0.2c0.3-0.2,0.5-0.3,0.8-0.5 c0.1-0.1,0.3-0.2,0.4-0.3c0.1-0.1,0.3-0.2,0.4-0.3L447,109.2V378.2z" />
            </svg>
            <span>Email</span>
        </button>
        <button type="button" class="btn btn-secondary gap-1 px-2 py-1 border-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16px" height="16px" fill="CurrentColor">
                <path d="m439.277344 72.722656c-46.898438-46.898437-109.238282-72.722656-175.566406-72.722656-.003907 0-.019532 0-.023438 0-32.804688.00390625-64.773438 6.355469-95.011719 18.882812-30.242187 12.527344-57.335937 30.640626-80.535156 53.839844-46.894531 46.894532-72.71875 109.246094-72.71875 175.566406 0 39.550782 9.542969 78.855469 27.625 113.875l-41.734375 119.226563c-2.941406 8.410156-.859375 17.550781 5.445312 23.851563 4.410157 4.414062 10.214844 6.757812 16.183594 6.757812 2.558594 0 5.144532-.429688 7.667969-1.3125l119.226563-41.730469c35.019531 18.082031 74.324218 27.625 113.875 27.625 66.320312 0 128.667968-25.828125 175.566406-72.722656 46.894531-46.894531 72.722656-109.246094 72.722656-175.566406 0-66.324219-25.824219-128.675781-72.722656-175.570313zm-21.234375 329.902344c-41.222657 41.226562-96.035157 63.925781-154.332031 63.925781-35.664063 0-71.09375-8.820312-102.460938-25.515625-5.6875-3.023437-12.410156-3.542968-18.445312-1.429687l-108.320313 37.910156 37.914063-108.320313c2.113281-6.042968 1.589843-12.765624-1.433594-18.449218-16.691406-31.359375-25.515625-66.789063-25.515625-102.457032 0-58.296874 22.703125-113.109374 63.925781-154.332031 41.21875-41.21875 96.023438-63.921875 154.316406-63.929687h.019532c58.300781 0 113.109374 22.703125 154.332031 63.929687 41.226562 41.222657 63.929687 96.03125 63.929687 154.332031 0 58.300782-22.703125 113.113282-63.929687 154.335938zm0 0"></path>
                <path d="m355.984375 270.46875c-11.421875-11.421875-30.007813-11.421875-41.429687 0l-12.492188 12.492188c-31.019531-16.902344-56.121094-42.003907-73.027344-73.023438l12.492188-12.492188c11.425781-11.421874 11.425781-30.007812 0-41.429687l-33.664063-33.664063c-11.421875-11.421874-30.007812-11.421874-41.429687 0l-26.929688 26.929688c-15.425781 15.425781-16.195312 41.945312-2.167968 74.675781 12.179687 28.417969 34.46875 59.652344 62.761718 87.945313 28.292969 28.292968 59.527344 50.582031 87.945313 62.761718 15.550781 6.664063 29.695312 9.988282 41.917969 9.988282 13.503906 0 24.660156-4.058594 32.757812-12.15625l26.929688-26.933594v.003906c5.535156-5.535156 8.582031-12.890625 8.582031-20.714844 0-7.828124-3.046875-15.183593-8.582031-20.714843zm-14.5 80.792969c-4.402344 4.402343-17.941406 5.945312-41.609375-4.195313-24.992188-10.710937-52.886719-30.742187-78.542969-56.398437s-45.683593-53.546875-56.394531-78.539063c-10.144531-23.667968-8.601562-37.210937-4.199219-41.613281l26.414063-26.414063 32.625 32.628907-15.636719 15.640625c-7.070313 7.070312-8.777344 17.792968-4.242187 26.683594 20.558593 40.3125 52.734374 72.488281 93.046874 93.046874 8.894532 4.535157 19.617188 2.832032 26.6875-4.242187l15.636719-15.636719 32.628907 32.628906zm0 0"></path>
            </svg>
        </button>
        <button type="button" class="btn btn-secondary gap-1 px-2 py-1 border-1">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 499.019 499.019" style="enable-background:new 0 0 499.019 499.019;" xml:space="preserve" width="16px" height="16px" fill="CurrentColor">
                <path d="M499.019,427.479c0-8.413-3.277-16.323-9.227-22.272L387.969,303.382c-12.282-12.281-32.266-12.279-44.548,0l-28.284,28.284 c-4.033,4.033-6.898,9.095-8.287,14.64l-0.91,3.643c-24.406-19.88-59.186-52.017-82.02-74.851 c-22.835-22.835-54.971-57.614-74.85-82.019l3.646-0.911c5.541-1.388,10.602-4.253,14.635-8.286l5.65-5.65 c0.002-0.002,0.005-0.004,0.007-0.006s0.004-0.005,0.006-0.007l22.621-22.622c12.281-12.281,12.281-32.266,0-44.548L93.812,9.227 c-5.949-5.95-13.859-9.227-22.272-9.227c0,0,0,0-0.001,0c-8.414,0-16.324,3.276-22.274,9.226L17.948,40.544 C-0.588,59.08-5.278,88.1,6.276,112.759c30.215,64.487,93.718,150.136,161.783,218.201 c68.065,68.065,153.713,131.568,218.197,161.78c9.015,4.225,18.611,6.277,28.066,6.277c16.411,0,32.393-6.187,44.153-17.946 l8.688-8.688l0.001-0.001c0,0,0.001-0.001,0.001-0.001l22.626-22.626C495.742,443.804,499.019,435.893,499.019,427.479z M59.871,19.834c3.117-3.117,7.261-4.834,11.668-4.833c4.407,0,8.55,1.716,11.667,4.833L185.03,121.656 c6.433,6.434,6.433,16.902,0,23.335l-17.324,17.324L42.547,37.158L59.871,19.834z M392.62,479.158 c-63.037-29.534-147.02-91.869-213.955-158.805c-66.935-66.935-129.27-150.918-158.807-213.957 c-8.883-18.96-5.389-41.161,8.696-55.245l3.386-3.386l125.157,125.157l-0.353,0.353c-2.113,2.113-4.766,3.615-7.669,4.342 l-14.921,3.729c-2.45,0.612-4.427,2.417-5.261,4.801c-0.833,2.384-0.412,5.026,1.123,7.032 c20.726,27.092,61.186,70.416,83.297,92.527c22.109,22.108,65.433,62.568,92.526,83.297c2.006,1.534,4.649,1.955,7.033,1.123 c2.384-0.834,4.188-2.812,4.801-5.262l3.728-14.918c0.728-2.906,2.229-5.56,4.342-7.672l0.353-0.353l125.158,125.158l-3.386,3.386 C433.784,484.549,411.582,488.045,392.62,479.158z M479.186,439.147l-17.324,17.325L336.704,331.314l17.324-17.324 c6.433-6.433,16.902-6.435,23.335,0l101.823,101.824c3.117,3.116,4.833,7.259,4.833,11.666 C484.019,431.887,482.303,436.031,479.186,439.147z" />
            </svg>
        </button>
        <button type="button" class="btn btn-secondary gap-1 px-2 py-1 border-1">
            <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="16px" height="16px" fill="CurrentColor">
                <path d="m6 27h20c1.654 0 3-1.346 3-3v-14c0-1.654-1.346-3-3-3h-3v-1c0-.553-.447-1-1-1s-1 .447-1 1v1h-10v-1c0-.553-.447-1-1-1s-1 .447-1 1v1h-3c-1.654 0-3 1.346-3 3v14c0 1.654 1.346 3 3 3zm0-18h3v1c0 .553.447 1 1 1s1-.447 1-1v-1h10v1c0 .553.447 1 1 1s1-.447 1-1v-1h3c.552 0 1 .448 1 1v14c0 .552-.448 1-1 1h-20c-.552 0-1-.448-1-1v-14c0-.552.448-1 1-1z" />
                <path d="m7 15h18c.553 0 1-.447 1-1s-.447-1-1-1h-18c-.553 0-1 .447-1 1s.447 1 1 1z" />
            </svg>
        </button>
    </div>
</div>
<div class="booking-lesson p-3 border-bottom">
    <div class="form-check border rounded-2 p-2 mb-2">
        <div class="d-flex">
            <span class="checkicon d-inline-flex me-2 align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 511.999 511.999" style="enable-background:new 0 0 511.999 511.999;" xml:space="preserve" width="12px" height="12px" fill="#1FC16B">
                    <path d="M511.923,416.776l-7.748-62.787c-0.002-0.013-0.003-0.026-0.005-0.04c-2.064-16.192-12.655-30.269-27.642-36.737 l-58.468-25.236c-0.061-0.026-0.124-0.044-0.185-0.07c-0.054-0.022-0.104-0.05-0.159-0.072l-4.873-1.892v-11.023 c13.391-10.093,22.29-26.088,24.403-44.179c3.314-2.211,5.64-5.854,6.028-10.139l2.18-24.998c0.335-3.628-0.769-7.165-3.107-9.958 c-0.716-0.855-1.521-1.609-2.397-2.252l0.028-0.509c0.01-0.184,0.015-0.368,0.015-0.551c0-32.516-28.132-58.97-62.711-58.97 c-20.668,0-39.026,9.456-50.457,24.005l0.016-0.3c0.01-0.183,0.015-0.367,0.015-0.551c0-36.69-31.788-66.54-70.859-66.54 c-39.07,0-70.856,29.85-70.856,66.54c0,0.185,0.005,0.37,0.015,0.554l0.016,0.298c-11.431-14.549-29.791-24.006-50.46-24.006 c-34.578,0-62.708,26.454-62.708,58.97c0,0.185,0.005,0.37,0.016,0.555l0.027,0.495c-0.876,0.642-1.681,1.393-2.397,2.246 c-2.34,2.787-3.451,6.318-3.129,9.928l2.191,25.046c0.317,3.631,2.03,6.918,4.825,9.255c0.393,0.329,0.8,0.633,1.221,0.912 c2.13,18.083,11.027,34.065,24.398,44.15v11.022l-4.874,1.893c-0.053,0.021-0.102,0.048-0.154,0.069 c-0.063,0.026-0.127,0.044-0.189,0.071l-58.463,25.236c-14.984,6.467-25.575,20.542-27.645,36.77l-7.756,62.791 c-0.351,2.844,0.535,5.702,2.432,7.849C4.403,426.77,7.131,428,9.996,428l101.71,0.022c0,0,0.002,0,0.003,0h288.58 c0.001,0,0.002,0,0.002,0L502.001,428c2.865,0,5.592-1.23,7.49-3.377C511.388,422.477,512.274,419.619,511.923,416.776z M316.107,233.859c0.393,0.329,0.801,0.633,1.221,0.913c2.131,18.081,11.028,34.065,24.398,44.149v11.022l-1.112,0.432 l-38.148-16.465c-0.061-0.026-0.124-0.045-0.185-0.07c-0.054-0.022-0.104-0.05-0.159-0.072l-6.612-2.567V256.64 c8.246-6.061,15.002-14.105,19.899-23.425C315.637,233.434,315.862,233.654,316.107,233.859z M362.047,321.409 c0.249,0.107,0.488,0.232,0.733,0.346c0.382,0.177,0.768,0.347,1.141,0.538c9.168,4.699,15.656,13.718,17.125,23.995 c0.026,0.181,0.055,0.362,0.095,0.591l1.758,13.792H268.735l34.283-64.739l33.519,14.467c0.001,0,0.002,0.001,0.003,0.001 L362.047,321.409z M201.27,168.943c2.934-1.966,4.613-5.335,4.418-8.861l-0.545-9.828c0.155-25.542,22.909-46.276,50.856-46.276 c27.948,0,50.703,20.736,50.858,46.278l-0.542,9.814c-0.193,3.501,1.461,6.847,4.36,8.819c0.646,0.439,1.333,0.796,2.046,1.07 l-1.543,17.694c-0.935,0.307-1.829,0.754-2.65,1.335c-2.489,1.76-4.039,4.56-4.211,7.603l-0.322,5.732 c-1.589,17.58-10.595,32.783-24.111,40.689c-3.897,2.28-5.784,6.889-4.602,11.247c0.067,0.249,0.144,0.492,0.228,0.732v23.054 c0,4.126,2.534,7.829,6.381,9.322l2.514,0.976l-28.407,53.645l-28.406-53.642l2.517-0.977c3.847-1.494,6.381-5.196,6.381-9.322 v-23.064c0.084-0.236,0.159-0.477,0.225-0.721c1.182-4.358-0.704-8.967-4.601-11.247c-13.501-7.9-22.505-23.106-24.109-40.694 l-0.323-5.74c-0.174-3.089-1.769-5.924-4.318-7.678c-0.797-0.548-1.66-0.972-2.559-1.266l-1.545-17.654 C199.959,169.716,200.634,169.369,201.27,168.943z M243.26,360.672H129.104l1.759-13.79c0.033-0.192,0.066-0.384,0.093-0.577 c0-0.001,0-0.002,0-0.003c0-0.001,0.001-0.003,0.001-0.004c1.465-10.28,7.953-19.302,17.122-24.003 c0.614-0.314,1.24-0.61,1.877-0.885l59.022-25.477L243.26,360.672z M194.675,234.757c0.419-0.279,0.825-0.583,1.217-0.912 c0.245-0.205,0.471-0.427,0.699-0.647c4.898,9.329,11.654,17.38,19.899,23.444v14.56l-6.614,2.568 c-0.054,0.021-0.104,0.049-0.158,0.071c-0.062,0.025-0.125,0.043-0.186,0.07l-38.144,16.465l-1.114-0.432v-11.023 C183.66,268.831,192.558,252.841,194.675,234.757z M102.903,408.021l-81.594-0.018l3.376-27.33h81.706L102.903,408.021z M108.942,360.673H27.155l0.518-4.198c1.176-9.212,7.202-17.22,15.727-20.9l49.985-21.576l10.873,20.533l6.462,12.203 L108.942,360.673z M121.879,318.586c-0.637,0.807-1.249,1.632-1.838,2.474c-0.054,0.078-0.112,0.153-0.166,0.231l-3.462-6.538 l-4.415-8.341l0.777-0.302c3.846-1.493,6.38-5.196,6.38-9.322v-19.755c0.053-0.163,0.103-0.329,0.148-0.496 c1.181-4.358-0.704-8.967-4.601-11.247c-11.313-6.62-18.865-19.393-20.221-34.187l-0.278-4.948 c-0.174-3.089-1.767-5.923-4.315-7.677c-0.545-0.375-1.121-0.692-1.718-0.949l-1.181-13.5c0.367-0.185,0.724-0.393,1.07-0.624 c2.934-1.967,4.613-5.335,4.417-8.862l-0.47-8.469c0.153-21.37,19.253-38.71,42.707-38.71c23.456,0,42.557,17.342,42.711,38.711 l-0.467,8.458c-0.193,3.499,1.46,6.845,4.356,8.817c0.359,0.245,0.73,0.463,1.112,0.656l-1.181,13.539 c-0.631,0.273-1.239,0.613-1.811,1.018c-2.487,1.76-4.036,4.559-4.207,7.601l-0.278,4.941 c-1.343,14.787-8.896,27.558-20.223,34.184c-3.898,2.28-5.784,6.889-4.603,11.247c0.046,0.17,0.097,0.339,0.151,0.506v19.746 c0,0.877,0.115,1.736,0.332,2.558l-8.575,3.702c-0.979,0.422-1.938,0.88-2.883,1.36c-0.1,0.051-0.202,0.098-0.301,0.15 c-0.864,0.446-1.709,0.92-2.543,1.412c-0.183,0.107-0.366,0.213-0.547,0.323c-0.759,0.461-1.502,0.942-2.234,1.44 c-0.249,0.169-0.496,0.339-0.742,0.512c-0.662,0.467-1.312,0.948-1.95,1.445c-0.303,0.235-0.601,0.476-0.899,0.718 c-0.563,0.458-1.119,0.925-1.661,1.406c-0.355,0.314-0.7,0.637-1.046,0.96c-0.316,0.296-0.627,0.597-0.935,0.9 c-0.552,0.542-1.091,1.095-1.618,1.66c-0.205,0.221-0.408,0.443-0.61,0.667c-0.612,0.68-1.205,1.375-1.781,2.086 C122.146,318.253,122.011,318.419,121.879,318.586z M123.065,408.022l1.447-11.342l2.042-16.008h258.894l3.485,27.35H123.065z M397.294,265.288c-3.898,2.28-5.784,6.889-4.603,11.247c0.046,0.17,0.097,0.339,0.151,0.506v19.746 c0,4.126,2.534,7.829,6.381,9.322l0.775,0.301l-4.01,7.573l-3.866,7.3c-0.046-0.066-0.094-0.13-0.14-0.195 c-0.6-0.86-1.225-1.702-1.875-2.524c-0.121-0.152-0.243-0.303-0.365-0.454c-0.589-0.728-1.195-1.439-1.822-2.134 c-0.189-0.21-0.379-0.418-0.572-0.625c-0.543-0.583-1.099-1.152-1.668-1.71c-0.292-0.287-0.586-0.573-0.886-0.853 c-0.367-0.343-0.735-0.685-1.112-1.017c-0.522-0.462-1.056-0.91-1.597-1.351c-0.307-0.25-0.615-0.498-0.928-0.741 c-0.629-0.49-1.271-0.964-1.923-1.425c-0.255-0.18-0.512-0.356-0.771-0.531c-0.723-0.491-1.457-0.967-2.207-1.423 c-0.191-0.116-0.385-0.227-0.577-0.341c-0.824-0.486-1.659-0.954-2.512-1.395c-0.112-0.058-0.227-0.112-0.34-0.169 c-0.928-0.471-1.87-0.92-2.83-1.335c-0.009-0.004-0.017-0.008-0.026-0.012l-8.578-3.702c0.217-0.822,0.331-1.68,0.331-2.556 v-19.755c0.053-0.163,0.103-0.329,0.148-0.496c1.181-4.358-0.704-8.967-4.601-11.247c-11.315-6.621-18.867-19.395-20.222-34.19 l-0.278-4.947c-0.174-3.088-1.768-5.922-4.315-7.676c-0.545-0.375-1.121-0.692-1.718-0.949l-1.181-13.5 c0.367-0.185,0.724-0.393,1.07-0.624c2.934-1.966,4.613-5.335,4.418-8.861l-0.47-8.469c0.154-21.371,19.253-38.713,42.708-38.713 c23.456,0,42.557,17.342,42.71,38.711l-0.467,8.458c-0.193,3.499,1.46,6.845,4.357,8.817c0.359,0.244,0.73,0.463,1.111,0.656 l-1.181,13.539c-0.632,0.273-1.239,0.613-1.811,1.018c-2.487,1.76-4.036,4.559-4.207,7.601l-0.278,4.942 C416.174,245.892,408.622,258.662,397.294,265.288z M409.095,408.021l-3.485-27.348h10.878c5.523,0,10-4.477,10-10 s-4.477-10-10-10h-13.426l-1.778-13.95l17.33-32.725l49.99,21.577c8.521,3.678,14.546,11.679,15.725,20.885l6.361,51.542 L409.095,408.021z" />
                </svg>
            </span>
            <label class="form-check-label">PRI1: 50, GRP 25, PTY:15</label>
            <?php if ($IS_CHARGED == 1) { ?>
                <span class="checkicon float-end ms-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="12px" height="12px" fill="#1FC16B">
                        <path d="M256,0C114.615,0,0,114.615,0,256s114.615,256,256,256s256-114.615,256-256S397.385,0,256,0z M219.429,367.932 L108.606,257.108l38.789-38.789l72.033,72.035L355.463,154.32l38.789,38.789L219.429,367.932z"></path>
                    </svg>
                </span>
            <?php } ?>
        </div>
        <div class="statusarea mt-1" style="margin-left: 27px;">
            <span>Private: 40/50</span>
            <span>Group: 10/10</span>
            <span>Practice: 5/15</span>
        </div>
    </div>
    <h6 class="f14">Next Booked Lesson</h6>
    <div class="form-check border rounded-2 p-2 mb-2">
        <div class="d-flex">
            <span class="checkicon d-inline-flex me-2 align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 511.999 511.999" style="enable-background:new 0 0 511.999 511.999;" xml:space="preserve" width="12px" height="12px" fill="#1FC16B">
                    <path d="M511.923,416.776l-7.748-62.787c-0.002-0.013-0.003-0.026-0.005-0.04c-2.064-16.192-12.655-30.269-27.642-36.737 l-58.468-25.236c-0.061-0.026-0.124-0.044-0.185-0.07c-0.054-0.022-0.104-0.05-0.159-0.072l-4.873-1.892v-11.023 c13.391-10.093,22.29-26.088,24.403-44.179c3.314-2.211,5.64-5.854,6.028-10.139l2.18-24.998c0.335-3.628-0.769-7.165-3.107-9.958 c-0.716-0.855-1.521-1.609-2.397-2.252l0.028-0.509c0.01-0.184,0.015-0.368,0.015-0.551c0-32.516-28.132-58.97-62.711-58.97 c-20.668,0-39.026,9.456-50.457,24.005l0.016-0.3c0.01-0.183,0.015-0.367,0.015-0.551c0-36.69-31.788-66.54-70.859-66.54 c-39.07,0-70.856,29.85-70.856,66.54c0,0.185,0.005,0.37,0.015,0.554l0.016,0.298c-11.431-14.549-29.791-24.006-50.46-24.006 c-34.578,0-62.708,26.454-62.708,58.97c0,0.185,0.005,0.37,0.016,0.555l0.027,0.495c-0.876,0.642-1.681,1.393-2.397,2.246 c-2.34,2.787-3.451,6.318-3.129,9.928l2.191,25.046c0.317,3.631,2.03,6.918,4.825,9.255c0.393,0.329,0.8,0.633,1.221,0.912 c2.13,18.083,11.027,34.065,24.398,44.15v11.022l-4.874,1.893c-0.053,0.021-0.102,0.048-0.154,0.069 c-0.063,0.026-0.127,0.044-0.189,0.071l-58.463,25.236c-14.984,6.467-25.575,20.542-27.645,36.77l-7.756,62.791 c-0.351,2.844,0.535,5.702,2.432,7.849C4.403,426.77,7.131,428,9.996,428l101.71,0.022c0,0,0.002,0,0.003,0h288.58 c0.001,0,0.002,0,0.002,0L502.001,428c2.865,0,5.592-1.23,7.49-3.377C511.388,422.477,512.274,419.619,511.923,416.776z M316.107,233.859c0.393,0.329,0.801,0.633,1.221,0.913c2.131,18.081,11.028,34.065,24.398,44.149v11.022l-1.112,0.432 l-38.148-16.465c-0.061-0.026-0.124-0.045-0.185-0.07c-0.054-0.022-0.104-0.05-0.159-0.072l-6.612-2.567V256.64 c8.246-6.061,15.002-14.105,19.899-23.425C315.637,233.434,315.862,233.654,316.107,233.859z M362.047,321.409 c0.249,0.107,0.488,0.232,0.733,0.346c0.382,0.177,0.768,0.347,1.141,0.538c9.168,4.699,15.656,13.718,17.125,23.995 c0.026,0.181,0.055,0.362,0.095,0.591l1.758,13.792H268.735l34.283-64.739l33.519,14.467c0.001,0,0.002,0.001,0.003,0.001 L362.047,321.409z M201.27,168.943c2.934-1.966,4.613-5.335,4.418-8.861l-0.545-9.828c0.155-25.542,22.909-46.276,50.856-46.276 c27.948,0,50.703,20.736,50.858,46.278l-0.542,9.814c-0.193,3.501,1.461,6.847,4.36,8.819c0.646,0.439,1.333,0.796,2.046,1.07 l-1.543,17.694c-0.935,0.307-1.829,0.754-2.65,1.335c-2.489,1.76-4.039,4.56-4.211,7.603l-0.322,5.732 c-1.589,17.58-10.595,32.783-24.111,40.689c-3.897,2.28-5.784,6.889-4.602,11.247c0.067,0.249,0.144,0.492,0.228,0.732v23.054 c0,4.126,2.534,7.829,6.381,9.322l2.514,0.976l-28.407,53.645l-28.406-53.642l2.517-0.977c3.847-1.494,6.381-5.196,6.381-9.322 v-23.064c0.084-0.236,0.159-0.477,0.225-0.721c1.182-4.358-0.704-8.967-4.601-11.247c-13.501-7.9-22.505-23.106-24.109-40.694 l-0.323-5.74c-0.174-3.089-1.769-5.924-4.318-7.678c-0.797-0.548-1.66-0.972-2.559-1.266l-1.545-17.654 C199.959,169.716,200.634,169.369,201.27,168.943z M243.26,360.672H129.104l1.759-13.79c0.033-0.192,0.066-0.384,0.093-0.577 c0-0.001,0-0.002,0-0.003c0-0.001,0.001-0.003,0.001-0.004c1.465-10.28,7.953-19.302,17.122-24.003 c0.614-0.314,1.24-0.61,1.877-0.885l59.022-25.477L243.26,360.672z M194.675,234.757c0.419-0.279,0.825-0.583,1.217-0.912 c0.245-0.205,0.471-0.427,0.699-0.647c4.898,9.329,11.654,17.38,19.899,23.444v14.56l-6.614,2.568 c-0.054,0.021-0.104,0.049-0.158,0.071c-0.062,0.025-0.125,0.043-0.186,0.07l-38.144,16.465l-1.114-0.432v-11.023 C183.66,268.831,192.558,252.841,194.675,234.757z M102.903,408.021l-81.594-0.018l3.376-27.33h81.706L102.903,408.021z M108.942,360.673H27.155l0.518-4.198c1.176-9.212,7.202-17.22,15.727-20.9l49.985-21.576l10.873,20.533l6.462,12.203 L108.942,360.673z M121.879,318.586c-0.637,0.807-1.249,1.632-1.838,2.474c-0.054,0.078-0.112,0.153-0.166,0.231l-3.462-6.538 l-4.415-8.341l0.777-0.302c3.846-1.493,6.38-5.196,6.38-9.322v-19.755c0.053-0.163,0.103-0.329,0.148-0.496 c1.181-4.358-0.704-8.967-4.601-11.247c-11.313-6.62-18.865-19.393-20.221-34.187l-0.278-4.948 c-0.174-3.089-1.767-5.923-4.315-7.677c-0.545-0.375-1.121-0.692-1.718-0.949l-1.181-13.5c0.367-0.185,0.724-0.393,1.07-0.624 c2.934-1.967,4.613-5.335,4.417-8.862l-0.47-8.469c0.153-21.37,19.253-38.71,42.707-38.71c23.456,0,42.557,17.342,42.711,38.711 l-0.467,8.458c-0.193,3.499,1.46,6.845,4.356,8.817c0.359,0.245,0.73,0.463,1.112,0.656l-1.181,13.539 c-0.631,0.273-1.239,0.613-1.811,1.018c-2.487,1.76-4.036,4.559-4.207,7.601l-0.278,4.941 c-1.343,14.787-8.896,27.558-20.223,34.184c-3.898,2.28-5.784,6.889-4.603,11.247c0.046,0.17,0.097,0.339,0.151,0.506v19.746 c0,0.877,0.115,1.736,0.332,2.558l-8.575,3.702c-0.979,0.422-1.938,0.88-2.883,1.36c-0.1,0.051-0.202,0.098-0.301,0.15 c-0.864,0.446-1.709,0.92-2.543,1.412c-0.183,0.107-0.366,0.213-0.547,0.323c-0.759,0.461-1.502,0.942-2.234,1.44 c-0.249,0.169-0.496,0.339-0.742,0.512c-0.662,0.467-1.312,0.948-1.95,1.445c-0.303,0.235-0.601,0.476-0.899,0.718 c-0.563,0.458-1.119,0.925-1.661,1.406c-0.355,0.314-0.7,0.637-1.046,0.96c-0.316,0.296-0.627,0.597-0.935,0.9 c-0.552,0.542-1.091,1.095-1.618,1.66c-0.205,0.221-0.408,0.443-0.61,0.667c-0.612,0.68-1.205,1.375-1.781,2.086 C122.146,318.253,122.011,318.419,121.879,318.586z M123.065,408.022l1.447-11.342l2.042-16.008h258.894l3.485,27.35H123.065z M397.294,265.288c-3.898,2.28-5.784,6.889-4.603,11.247c0.046,0.17,0.097,0.339,0.151,0.506v19.746 c0,4.126,2.534,7.829,6.381,9.322l0.775,0.301l-4.01,7.573l-3.866,7.3c-0.046-0.066-0.094-0.13-0.14-0.195 c-0.6-0.86-1.225-1.702-1.875-2.524c-0.121-0.152-0.243-0.303-0.365-0.454c-0.589-0.728-1.195-1.439-1.822-2.134 c-0.189-0.21-0.379-0.418-0.572-0.625c-0.543-0.583-1.099-1.152-1.668-1.71c-0.292-0.287-0.586-0.573-0.886-0.853 c-0.367-0.343-0.735-0.685-1.112-1.017c-0.522-0.462-1.056-0.91-1.597-1.351c-0.307-0.25-0.615-0.498-0.928-0.741 c-0.629-0.49-1.271-0.964-1.923-1.425c-0.255-0.18-0.512-0.356-0.771-0.531c-0.723-0.491-1.457-0.967-2.207-1.423 c-0.191-0.116-0.385-0.227-0.577-0.341c-0.824-0.486-1.659-0.954-2.512-1.395c-0.112-0.058-0.227-0.112-0.34-0.169 c-0.928-0.471-1.87-0.92-2.83-1.335c-0.009-0.004-0.017-0.008-0.026-0.012l-8.578-3.702c0.217-0.822,0.331-1.68,0.331-2.556 v-19.755c0.053-0.163,0.103-0.329,0.148-0.496c1.181-4.358-0.704-8.967-4.601-11.247c-11.315-6.621-18.867-19.395-20.222-34.19 l-0.278-4.947c-0.174-3.088-1.768-5.922-4.315-7.676c-0.545-0.375-1.121-0.692-1.718-0.949l-1.181-13.5 c0.367-0.185,0.724-0.393,1.07-0.624c2.934-1.966,4.613-5.335,4.418-8.861l-0.47-8.469c0.154-21.371,19.253-38.713,42.708-38.713 c23.456,0,42.557,17.342,42.71,38.711l-0.467,8.458c-0.193,3.499,1.46,6.845,4.357,8.817c0.359,0.244,0.73,0.463,1.111,0.656 l-1.181,13.539c-0.632,0.273-1.239,0.613-1.811,1.018c-2.487,1.76-4.036,4.559-4.207,7.601l-0.278,4.942 C416.174,245.892,408.622,258.662,397.294,265.288z M409.095,408.021l-3.485-27.348h10.878c5.523,0,10-4.477,10-10 s-4.477-10-10-10h-13.426l-1.778-13.95l17.33-32.725l49.99,21.577c8.521,3.678,14.546,11.679,15.725,20.885l6.361,51.542 L409.095,408.021z" />
                </svg>
            </span>
            <label class="form-check-label">Salsa Beginner, Grp</label>
            <span class="badge border fw-normal theme-text-light ms-auto">GRP</span>
            <span class="badge bg-light fw-normal theme-text-light ms-1">1 of 4</span>
        </div>
        <div class="statusareatext mt-1 f12 theme-text-light" style="margin-left: 27px;">
            <span class="text-uppercase">Monday Dec 15, 8:00 - 9:00 AM (PST)</span>
            <ul class="list-inline mb-0 mt-1">
                <li class="list-inline-item fw-semibold">
                    <span class="namebadge badge badgeprimary badge-pill px-1">CB</span>
                    <span class="name">Chandler Bing</span>
                </li>
                <li class="list-inline-item fw-semibold">
                    <span class="badge rounded-pill bg-secondary p-1 d-inline-block me-1"></span>
                    <span class="name">Studio Location</span>
                </li>
            </ul>
        </div>
    </div>
</div>
<form class="mb-0 appointmentform p-3">
    <div class="row mb-2 align-items-center">
        <div class="col-5 col-md-5">
            <div class="d-flex gap-2 align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="24px" height="24px" fill="#ccc">
                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                </svg>
                <label class="mb-0">Service Provider</label>
            </div>
        </div>
        <div class="col-7 col-md-7">
            <div class="form-group serviceprovider">
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
        </div>
    </div>
    <div class="row mb-3 align-items-center">
        <div class="col-5 col-md-5">
            <div class="d-flex gap-2 align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="24px" height="24px" fill="transparent">
                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                </svg>
                <label class="mb-0">Customer</label>
            </div>
        </div>
        <div class="col-7 col-md-7">
            <div class="form-group">
                <select class="form-control customerselect">
                    <option value="" selected disabled>-- Select --</option>
                    <option value="Ross Geller">Ross Geller</option>
                    <option value="Rachel Green">Rachel Green</option>
                    <option value="Chandler Bing">Chandler Bing</option>
                </select>
            </div>
        </div>
    </div>
    <div class="row mb-3 d-none enrollmentarea">
        <div class="col-5 col-md-5">
            <div class="d-flex gap-2 align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="24px" height="24px" fill="transparent">
                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                </svg>
                <label class="mb-0">Enrollment ID</label>
            </div>
        </div>
        <div class="col-7 col-md-7">
            <div class="form-group">
                <div class="form-check border rounded-2 p-2 mb-2">
                    <input class="form-check-input ms-0 me-1" type="radio" name="Enrollment" id="male" checked>
                    <label class="form-check-label" for="male">PRI1: 50, GRP 25, PTY:15</label>
                    <span class="checkicon float-end">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="12px" height="12px" fill="#1FC16B">
                            <path d="M256,0C114.615,0,0,114.615,0,256s114.615,256,256,256s256-114.615,256-256S397.385,0,256,0z M219.429,367.932 L108.606,257.108l38.789-38.789l72.033,72.035L355.463,154.32l38.789,38.789L219.429,367.932z" />
                        </svg>
                    </span>
                    <div class="statusarea mt-1">
                        <span>Private: 40/50</span>
                        <span>Group: 10/10</span>
                        <span>Practice: 5/15</span>
                    </div>
                </div>
                <div class="form-check border rounded-2 p-2 mb-2">
                    <input class="form-check-input ms-0 me-1" type="radio" name="Enrollment" id="female">
                    <label class="form-check-label" for="female">AD-hoc</label>
                    <span class="checkicon float-end">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="12px" height="12px" fill="#1FC16B">
                            <path d="M256,0C114.615,0,0,114.615,0,256s114.615,256,256,256s256-114.615,256-256S397.385,0,256,0z M219.429,367.932 L108.606,257.108l38.789-38.789l72.033,72.035L355.463,154.32l38.789,38.789L219.429,367.932z" />
                        </svg>
                    </span>
                    <div class="statusarea mt-1">
                        <span>Private: 40/50</span>
                        <span>Group: 10/10</span>
                        <span>Practice: 5/15</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3 align-items-center schedulecode">
        <div class="col-5 col-md-5">
            <div class="d-flex gap-2 align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="24px" height="24px" fill="transparent">
                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                </svg>
                <label class="mb-0">Scheduling Code</label>
            </div>
        </div>
        <div class="col-7 col-md-7">
            <div class="form-group" id="scheduling_code_select">
                <select class=" form-control" required name="PK_SCHEDULING_CODE" id="PK_SCHEDULING_CODE">
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
        </div>
    </div>
    <hr class="mb-3">
    <div class="row mb-3">
        <div class="col-5 col-md-5">
            <div class="d-flex gap-2 align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 55.668 55.668" xml:space="preserve" width="24px" height="19px" fill="#ccc">
                    <path d="M27.833,0C12.487,0,0,12.486,0,27.834s12.487,27.834,27.833,27.834 c15.349,0,27.834-12.486,27.834-27.834S43.182,0,27.833,0z M27.833,51.957c-13.301,0-24.122-10.821-24.122-24.123 S14.533,3.711,27.833,3.711c13.303,0,24.123,10.821,24.123,24.123S41.137,51.957,27.833,51.957z" />
                    <path d="M41.618,25.819H29.689V10.046c0-1.025-0.831-1.856-1.855-1.856c-1.023,0-1.854,0.832-1.854,1.856 v19.483h15.638c1.024,0,1.855-0.83,1.854-1.855C43.472,26.65,42.64,25.819,41.618,25.819z" />
                </svg>
                <label class="mb-0">Date & Time</label>
            </div>
        </div>
        <div class="col-7 col-md-7">
            <div class="form-group d-flex gap-3" id="datetime">
                <input type="date" class="form-control" name="DATE" value="<?php echo $date_for_html_input; ?>" style="min-width: 110px;">
                <input type="time" class="form-control" name="START_TIME" value="<?php echo $start_time_for_input; ?>">
            </div>
            <button type="button" class="btn-available fw-semibold f12 bg-transparent p-0 border-0 d-flex align-items-center gap-2 ms-auto mt-2">
                <span>Show Availability</span>
                <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" enable-background="new 0 0 512 512" viewBox="0 0 512 512" width="13px" height="13px" fill="#000">
                    <path d="m256 374.3c-3 0-6-1.1-8.2-3.4l-213.4-213.3c-4.6-4.6-4.6-11.9 0-16.5s11.9-4.6 16.5 0l205.1 205.1 205.1-205.1c4.6-4.6 11.9-4.6 16.5 0s4.6 11.9 0 16.5l-213.4 213.3c-2.2 2.3-5.2 3.4-8.2 3.4z" />
                </svg>
            </button>
            <div class="Availabilityarea mt-2" style="display: none;">
                <span>08:00 AM - 09:00 AM</span>
                <span>09:00 AM - 10:00 AM</span>
                <span>10:00 AM - 11:00 AM</span>
                <span>04:00 PM - 05:00 PM</span>
                <span>05:00 PM - 06:00 PM</span>
                <span>06:00 PM - 07:00 PM</span>
            </div>

        </div>
    </div>
    <hr class="mb-3">
    <div class="row mb-3">
        <div class="col-5 col-md-5">
            <div class="d-flex gap-2 align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="24px" height="18px" fill="#ccc">
                    <path d="M487.104,24.954c-33.274-33.269-87.129-33.273-120.407,0L51.948,339.665c-2.098,2.097-3.834,4.825-4.831,7.817 L1.057,485.647c-5.2,15.598,9.679,30.503,25.298,25.296l138.182-46.055c2.922-0.974,5.665-2.678,7.819-4.831l314.748-314.711 C520.299,112.154,520.299,58.146,487.104,24.954z M51.654,460.352l23.177-69.525l46.356,46.35L51.654,460.352z M158.214,417.634 l-63.837-63.829l267.272-267.24l63.837,63.83L158.214,417.634z M458.818,117.065l-5.049,5.049l-63.837-63.83l5.049-5.048 c17.602-17.597,46.239-17.597,63.837,0C476.419,70.833,476.419,99.467,458.818,117.065z" />
                </svg>
                <label class="mb-0">Public Note</label>
            </div>
        </div>
        <div class="col-7 col-md-7">
            <div class="form-group">
                <textarea class="form-control"><?= $COMMENT ?></textarea>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-5 col-md-5">
            <div class="d-flex gap-2 align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="24px" height="19px" fill="transparent">
                    <path d="M487.104,24.954c-33.274-33.269-87.129-33.273-120.407,0L51.948,339.665c-2.098,2.097-3.834,4.825-4.831,7.817 L1.057,485.647c-5.2,15.598,9.679,30.503,25.298,25.296l138.182-46.055c2.922-0.974,5.665-2.678,7.819-4.831l314.748-314.711 C520.299,112.154,520.299,58.146,487.104,24.954z M51.654,460.352l23.177-69.525l46.356,46.35L51.654,460.352z M158.214,417.634 l-63.837-63.829l267.272-267.24l63.837,63.83L158.214,417.634z M458.818,117.065l-5.049,5.049l-63.837-63.83l5.049-5.048 c17.602-17.597,46.239-17.597,63.837,0C476.419,70.833,476.419,99.467,458.818,117.065z" />
                </svg>
                <label class="mb-0">Internal Note</label>
            </div>
        </div>
        <div class="col-7 col-md-7">
            <div class="form-group">
                <textarea class="form-control"><?= $INTERNAL_COMMENT ?></textarea>
            </div>
        </div>
    </div>
</form>

<!-- End Appointment Details -->