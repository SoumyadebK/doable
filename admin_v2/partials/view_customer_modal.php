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

// Check if we're getting PK_USER or PK_USER_MASTER
if (isset($_POST['PK_USER_MASTER'])) {
    $PK_USER_MASTER = $_POST['PK_USER_MASTER'];
    // Get PK_USER from PK_USER_MASTER
    $user_master = $db->Execute("SELECT PK_USER FROM DOA_USER_MASTER WHERE PK_USER_MASTER = '$PK_USER_MASTER'");
    $PK_USER = $user_master->fields['PK_USER'];
} elseif (isset($_POST['PK_USER'])) {
    $PK_USER = $_POST['PK_USER'];
    // Get PK_USER_MASTER from PK_USER
    $user_master = $db->Execute("SELECT PK_USER_MASTER FROM DOA_USER_MASTER WHERE PK_USER = '$PK_USER'");
    $PK_USER_MASTER = $user_master->fields['PK_USER_MASTER'];
} else {
    // If neither parameter is provided, redirect
    header("location:all_customers.php");
    exit;
}

$PK_ENROLLMENT_MASTER = isset($_POST['PK_ENROLLMENT_MASTER']) ? $_POST['PK_ENROLLMENT_MASTER'] : 0;

// Then update your query to use PK_USER
$res = $db->Execute("SELECT * FROM DOA_USERS JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USERS.PK_USER = " . $PK_USER);

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

$user_interest_other_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_INTEREST_OTHER_DATA` WHERE `PK_USER_MASTER` = '$PK_USER_MASTER'");
if ($user_interest_other_data->RecordCount() > 0) {
    $WHAT_PROMPTED_YOU_TO_INQUIRE = $user_interest_other_data->fields['WHAT_PROMPTED_YOU_TO_INQUIRE'];
    $PK_SKILL_LEVEL = $user_interest_other_data->fields['PK_SKILL_LEVEL'];
    $PK_INQUIRY_METHOD = $user_interest_other_data->fields['PK_INQUIRY_METHOD'];
    $INQUIRY_TAKER_ID = $user_interest_other_data->fields['INQUIRY_TAKER_ID'];
}

$customer_details = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = '$PK_USER_MASTER'");
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

$selected_primary_location = $db->Execute("SELECT DOA_USER_MASTER.PRIMARY_LOCATION_ID, DOA_LOCATION.LOCATION_NAME FROM DOA_USER_MASTER LEFT JOIN DOA_LOCATION ON DOA_USER_MASTER.PRIMARY_LOCATION_ID = DOA_LOCATION.PK_LOCATION WHERE PK_USER_MASTER = '$PK_USER_MASTER'");
$primary_location = $selected_primary_location->fields['PRIMARY_LOCATION_ID'];
$LOCATION_NAME = $selected_primary_location->fields['LOCATION_NAME'];

if ($PK_USER_MASTER > 0) {
    makeExpiryEnrollmentComplete($PK_USER_MASTER);
    makeMiscComplete($PK_USER_MASTER);
    makeDroppedCancelled($PK_USER_MASTER);
    checkAllEnrollmentStatus($PK_USER_MASTER);
}

$selected_enrollment = '';
$row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_PACKAGE.PACKAGE_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.PK_LOCATION, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.CHARGE_TYPE, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT FROM DOA_ENROLLMENT_MASTER RIGHT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_PACKAGE ON DOA_ENROLLMENT_MASTER.PK_PACKAGE = DOA_PACKAGE.PK_PACKAGE WHERE DOA_SERVICE_MASTER.PK_SERVICE_CLASS != 5 AND DOA_SERVICE_CODE.IS_GROUP != 1 AND ((DOA_ENROLLMENT_MASTER.STATUS = 'A' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0) OR (DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = $PK_ENROLLMENT_MASTER)) AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $PK_USER_MASTER);
while (!$row->EOF) {
    $name = $row->fields['ENROLLMENT_NAME'];
    if (empty($name)) {
        $enrollment_name = ' ';
    } else {
        $enrollment_name = "$name" . " || ";
    }

    $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_SERVICE.SESSION_CREATED, DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
    $serviceCode = [];
    while (!$serviceCodeData->EOF) {
        $serviceCode[] = $serviceCodeData->fields['SERVICE_CODE'] . ': ' . $serviceCodeData->fields['NUMBER_OF_SESSION'];
        $serviceCodeData->MoveNext();
    }

    $PACKAGE_NAME = $row->fields['PACKAGE_NAME'];
    if (empty($PACKAGE_NAME)) {
        $PACKAGE = ' ';
    } else {
        $PACKAGE = " || " . "$PACKAGE_NAME";
    }

    if ($row->fields['CHARGE_TYPE'] == 'Membership') {
        $NUMBER_OF_SESSION = 99; //getAllSessionCreatedCount($row->fields['PK_ENROLLMENT_SERVICE'], 'NORMAL');
    } else {
        $NUMBER_OF_SESSION = $row->fields['NUMBER_OF_SESSION'];
    }

    $PRICE_PER_SESSION = $row->fields['PRICE_PER_SESSION'];
    $TOTAL_AMOUNT_PAID = ($row->fields['TOTAL_AMOUNT_PAID'] != null) ? $row->fields['TOTAL_AMOUNT_PAID'] : 0;
    $USED_NORMAL_SESSION_COUNT = getAllSessionCreatedCount($row->fields['PK_ENROLLMENT_SERVICE'], 'NORMAL');
    $USED_GROUP_SESSION_COUNT = getAllSessionCreatedCount($row->fields['PK_ENROLLMENT_SERVICE'], 'GROUP');
    $USED_PRACTICE_SESSION_COUNT = getAllSessionCreatedCount($row->fields['PK_ENROLLMENT_SERVICE'], 'AD_HOC');
    $paid_session = ($PRICE_PER_SESSION > 0) ? number_format(($TOTAL_AMOUNT_PAID / $PRICE_PER_SESSION), 2) : $NUMBER_OF_SESSION;

    if ($PK_ENROLLMENT_MASTER == $row->fields['PK_ENROLLMENT_MASTER']) {
        $selected_enrollment = $row->fields['ENROLLMENT_ID'];
    }
    $enrollment = $enrollment_name . $row->fields['PK_ENROLLMENT_MASTER'] . ' || ' . $PACKAGE . $row->fields['SERVICE_NAME'] . ' || ' . $row->fields['SERVICE_CODE'] . ' || ' . $USED_NORMAL_SESSION_COUNT . '/' . $NUMBER_OF_SESSION . ' || Paid : ' . $paid_session;

    $row->MoveNext();
}
?>

<div class="customer-profile d-flex p-3">
    <div class="d-flex align-items-center gap-3 f12 theme-text-light">
        <?php
        $selected_customer = trim($FIRST_NAME . ' ' . $LAST_NAME);
        $profile = getProfileBadge($selected_customer);
        $profile_name = $profile['initials'];
        $profile_color = $profile['color'];
        ?>
        <div class="profilename-short d-flex align-items-center justify-content-center fw-semibold" style="font-size: 20px; background-color: <?= $profile_color ?>; color: #fff;">
            <?= $profile_name ?>
        </div>
        <div class="profilename-data">
            <h6 class="mb-1">
                <?= $selected_customer; ?>
            </h6>
            <span><?= htmlspecialchars($LOCATION_NAME ?: 'Studio Location'); ?></span>
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
<!-- Tabs Nav -->
<ul class="nav nav-tabs align-items-center nav-fill" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#Details" type="button">Details</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#Appointment1" type="button">Appointment</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#Family" type="button">Family</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#Payment" type="button">
            <span>Payment</span>
            <span class="badge bg-danger rounded-pill">1</span>
        </button>
    </li>
</ul>
<!-- Tabs Content -->
<div class="tab-content" id="myTabContent2">
    <div class="tab-pane fade show active" id="Details" role="tabpanel">
        <div class="booking-lesson p-3 border-bottom">
            <h6 class="f14">
                <span>Active Enrollments</span>
                <span class="badge bg-secondary rounded-pill">1</span>
            </h6>
            <div class="form-check border rounded-2 p-2 mb-2">
                <div class="d-flex">
                    <span class="checkicon d-inline-flex me-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 511.999 511.999" style="enable-background:new 0 0 511.999 511.999;" xml:space="preserve" width="12px" height="12px" fill="#1FC16B">
                            <path d="M511.923,416.776l-7.748-62.787c-0.002-0.013-0.003-0.026-0.005-0.04c-2.064-16.192-12.655-30.269-27.642-36.737 l-58.468-25.236c-0.061-0.026-0.124-0.044-0.185-0.07c-0.054-0.022-0.104-0.05-0.159-0.072l-4.873-1.892v-11.023 c13.391-10.093,22.29-26.088,24.403-44.179c3.314-2.211,5.64-5.854,6.028-10.139l2.18-24.998c0.335-3.628-0.769-7.165-3.107-9.958 c-0.716-0.855-1.521-1.609-2.397-2.252l0.028-0.509c0.01-0.184,0.015-0.368,0.015-0.551c0-32.516-28.132-58.97-62.711-58.97 c-20.668,0-39.026,9.456-50.457,24.005l0.016-0.3c0.01-0.183,0.015-0.367,0.015-0.551c0-36.69-31.788-66.54-70.859-66.54 c-39.07,0-70.856,29.85-70.856,66.54c0,0.185,0.005,0.37,0.015,0.554l0.016,0.298c-11.431-14.549-29.791-24.006-50.46-24.006 c-34.578,0-62.708,26.454-62.708,58.97c0,0.185,0.005,0.37,0.016,0.555l0.027,0.495c-0.876,0.642-1.681,1.393-2.397,2.246 c-2.34,2.787-3.451,6.318-3.129,9.928l2.191,25.046c0.317,3.631,2.03,6.918,4.825,9.255c0.393,0.329,0.8,0.633,1.221,0.912 c2.13,18.083,11.027,34.065,24.398,44.15v11.022l-4.874,1.893c-0.053,0.021-0.102,0.048-0.154,0.069 c-0.063,0.026-0.127,0.044-0.189,0.071l-58.463,25.236c-14.984,6.467-25.575,20.542-27.645,36.77l-7.756,62.791 c-0.351,2.844,0.535,5.702,2.432,7.849C4.403,426.77,7.131,428,9.996,428l101.71,0.022c0,0,0.002,0,0.003,0h288.58 c0.001,0,0.002,0,0.002,0L502.001,428c2.865,0,5.592-1.23,7.49-3.377C511.388,422.477,512.274,419.619,511.923,416.776z M316.107,233.859c0.393,0.329,0.801,0.633,1.221,0.913c2.131,18.081,11.028,34.065,24.398,44.149v11.022l-1.112,0.432 l-38.148-16.465c-0.061-0.026-0.124-0.045-0.185-0.07c-0.054-0.022-0.104-0.05-0.159-0.072l-6.612-2.567V256.64 c8.246-6.061,15.002-14.105,19.899-23.425C315.637,233.434,315.862,233.654,316.107,233.859z M362.047,321.409 c0.249,0.107,0.488,0.232,0.733,0.346c0.382,0.177,0.768,0.347,1.141,0.538c9.168,4.699,15.656,13.718,17.125,23.995 c0.026,0.181,0.055,0.362,0.095,0.591l1.758,13.792H268.735l34.283-64.739l33.519,14.467c0.001,0,0.002,0.001,0.003,0.001 L362.047,321.409z M201.27,168.943c2.934-1.966,4.613-5.335,4.418-8.861l-0.545-9.828c0.155-25.542,22.909-46.276,50.856-46.276 c27.948,0,50.703,20.736,50.858,46.278l-0.542,9.814c-0.193,3.501,1.461,6.847,4.36,8.819c0.646,0.439,1.333,0.796,2.046,1.07 l-1.543,17.694c-0.935,0.307-1.829,0.754-2.65,1.335c-2.489,1.76-4.039,4.56-4.211,7.603l-0.322,5.732 c-1.589,17.58-10.595,32.783-24.111,40.689c-3.897,2.28-5.784,6.889-4.602,11.247c0.067,0.249,0.144,0.492,0.228,0.732v23.054 c0,4.126,2.534,7.829,6.381,9.322l2.514,0.976l-28.407,53.645l-28.406-53.642l2.517-0.977c3.847-1.494,6.381-5.196,6.381-9.322 v-23.064c0.084-0.236,0.159-0.477,0.225-0.721c1.182-4.358-0.704-8.967-4.601-11.247c-13.501-7.9-22.505-23.106-24.109-40.694 l-0.323-5.74c-0.174-3.089-1.769-5.924-4.318-7.678c-0.797-0.548-1.66-0.972-2.559-1.266l-1.545-17.654 C199.959,169.716,200.634,169.369,201.27,168.943z M243.26,360.672H129.104l1.759-13.79c0.033-0.192,0.066-0.384,0.093-0.577 c0-0.001,0-0.002,0-0.003c0-0.001,0.001-0.003,0.001-0.004c1.465-10.28,7.953-19.302,17.122-24.003 c0.614-0.314,1.24-0.61,1.877-0.885l59.022-25.477L243.26,360.672z M194.675,234.757c0.419-0.279,0.825-0.583,1.217-0.912 c0.245-0.205,0.471-0.427,0.699-0.647c4.898,9.329,11.654,17.38,19.899,23.444v14.56l-6.614,2.568 c-0.054,0.021-0.104,0.049-0.158,0.071c-0.062,0.025-0.125,0.043-0.186,0.07l-38.144,16.465l-1.114-0.432v-11.023 C183.66,268.831,192.558,252.841,194.675,234.757z M102.903,408.021l-81.594-0.018l3.376-27.33h81.706L102.903,408.021z M108.942,360.673H27.155l0.518-4.198c1.176-9.212,7.202-17.22,15.727-20.9l49.985-21.576l10.873,20.533l6.462,12.203 L108.942,360.673z M121.879,318.586c-0.637,0.807-1.249,1.632-1.838,2.474c-0.054,0.078-0.112,0.153-0.166,0.231l-3.462-6.538 l-4.415-8.341l0.777-0.302c3.846-1.493,6.38-5.196,6.38-9.322v-19.755c0.053-0.163,0.103-0.329,0.148-0.496 c1.181-4.358-0.704-8.967-4.601-11.247c-11.313-6.62-18.865-19.393-20.221-34.187l-0.278-4.948 c-0.174-3.089-1.767-5.923-4.315-7.677c-0.545-0.375-1.121-0.692-1.718-0.949l-1.181-13.5c0.367-0.185,0.724-0.393,1.07-0.624 c2.934-1.967,4.613-5.335,4.417-8.862l-0.47-8.469c0.153-21.37,19.253-38.71,42.707-38.71c23.456,0,42.557,17.342,42.711,38.711 l-0.467,8.458c-0.193,3.499,1.46,6.845,4.356,8.817c0.359,0.245,0.73,0.463,1.112,0.656l-1.181,13.539 c-0.631,0.273-1.239,0.613-1.811,1.018c-2.487,1.76-4.036,4.559-4.207,7.601l-0.278,4.941 c-1.343,14.787-8.896,27.558-20.223,34.184c-3.898,2.28-5.784,6.889-4.603,11.247c0.046,0.17,0.097,0.339,0.151,0.506v19.746 c0,0.877,0.115,1.736,0.332,2.558l-8.575,3.702c-0.979,0.422-1.938,0.88-2.883,1.36c-0.1,0.051-0.202,0.098-0.301,0.15 c-0.864,0.446-1.709,0.92-2.543,1.412c-0.183,0.107-0.366,0.213-0.547,0.323c-0.759,0.461-1.502,0.942-2.234,1.44 c-0.249,0.169-0.496,0.339-0.742,0.512c-0.662,0.467-1.312,0.948-1.95,1.445c-0.303,0.235-0.601,0.476-0.899,0.718 c-0.563,0.458-1.119,0.925-1.661,1.406c-0.355,0.314-0.7,0.637-1.046,0.96c-0.316,0.296-0.627,0.597-0.935,0.9 c-0.552,0.542-1.091,1.095-1.618,1.66c-0.205,0.221-0.408,0.443-0.61,0.667c-0.612,0.68-1.205,1.375-1.781,2.086 C122.146,318.253,122.011,318.419,121.879,318.586z M123.065,408.022l1.447-11.342l2.042-16.008h258.894l3.485,27.35H123.065z M397.294,265.288c-3.898,2.28-5.784,6.889-4.603,11.247c0.046,0.17,0.097,0.339,0.151,0.506v19.746 c0,4.126,2.534,7.829,6.381,9.322l0.775,0.301l-4.01,7.573l-3.866,7.3c-0.046-0.066-0.094-0.13-0.14-0.195 c-0.6-0.86-1.225-1.702-1.875-2.524c-0.121-0.152-0.243-0.303-0.365-0.454c-0.589-0.728-1.195-1.439-1.822-2.134 c-0.189-0.21-0.379-0.418-0.572-0.625c-0.543-0.583-1.099-1.152-1.668-1.71c-0.292-0.287-0.586-0.573-0.886-0.853 c-0.367-0.343-0.735-0.685-1.112-1.017c-0.522-0.462-1.056-0.91-1.597-1.351c-0.307-0.25-0.615-0.498-0.928-0.741 c-0.629-0.49-1.271-0.964-1.923-1.425c-0.255-0.18-0.512-0.356-0.771-0.531c-0.723-0.491-1.457-0.967-2.207-1.423 c-0.191-0.116-0.385-0.227-0.577-0.341c-0.824-0.486-1.659-0.954-2.512-1.395c-0.112-0.058-0.227-0.112-0.34-0.169 c-0.928-0.471-1.87-0.92-2.83-1.335c-0.009-0.004-0.017-0.008-0.026-0.012l-8.578-3.702c0.217-0.822,0.331-1.68,0.331-2.556 v-19.755c0.053-0.163,0.103-0.329,0.148-0.496c1.181-4.358-0.704-8.967-4.601-11.247c-11.315-6.621-18.867-19.395-20.222-34.19 l-0.278-4.947c-0.174-3.088-1.768-5.922-4.315-7.676c-0.545-0.375-1.121-0.692-1.718-0.949l-1.181-13.5 c0.367-0.185,0.724-0.393,1.07-0.624c2.934-1.966,4.613-5.335,4.418-8.861l-0.47-8.469c0.154-21.371,19.253-38.713,42.708-38.713 c23.456,0,42.557,17.342,42.71,38.711l-0.467,8.458c-0.193,3.499,1.46,6.845,4.357,8.817c0.359,0.244,0.73,0.463,1.111,0.656 l-1.181,13.539c-0.632,0.273-1.239,0.613-1.811,1.018c-2.487,1.76-4.036,4.559-4.207,7.601l-0.278,4.942 C416.174,245.892,408.622,258.662,397.294,265.288z M409.095,408.021l-3.485-27.348h10.878c5.523,0,10-4.477,10-10 s-4.477-10-10-10h-13.426l-1.778-13.95l17.33-32.725l49.99,21.577c8.521,3.678,14.546,11.679,15.725,20.885l6.361,51.542 L409.095,408.021z" />
                        </svg>
                    </span>
                    <label class="form-check-label"><?php echo $enrollment_name . $row->fields['ENROLLMENT_ID'] . $PACKAGE ?></label>
                    <?php if ($TOTAL_AMOUNT_PAID >= $row->fields['FINAL_AMOUNT']) { ?>
                        <span class="checkicon float-end ms-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="12px" height="12px" fill="#1FC16B">
                                <path d="M256,0C114.615,0,0,114.615,0,256s114.615,256,256,256s256-114.615,256-256S397.385,0,256,0z M219.429,367.932 L108.606,257.108l38.789-38.789l72.033,72.035L355.463,154.32l38.789,38.789L219.429,367.932z"></path>
                            </svg>
                        </span>
                    <?php } ?>
                </div>
                <div class="statusarea mt-1" style="margin-left: 27px;">
                    <span>Private: <?php echo $USED_NORMAL_SESSION_COUNT . '/' . $NUMBER_OF_SESSION ?></span>
                    <span>Group: <?php echo $USED_GROUP_SESSION_COUNT . '/' . $NUMBER_OF_SESSION ?></span>
                    <span>Practice: 0/0</span>
                    <span>Paid : $<?php echo $TOTAL_AMOUNT_PAID; ?></span>
                </div>
            </div>
            <button type="button" class="btn-secondary w-100 m-1">Add Enrollment</button>
        </div>
        <div class="form-group p-3">
            <label class="mb-2">Internal Note</label>
            <textarea class="form-control"></textarea>
        </div>
    </div>
    <?php
    // Fetch appointments for this user
    $appointments = $db_account->Execute("SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
                            DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_SERVICE AS APT_ENR_SERVICE,
                            DOA_APPOINTMENT_MASTER.GROUP_NAME,
                            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
                            DOA_APPOINTMENT_MASTER.DATE,
                            DOA_APPOINTMENT_MASTER.START_TIME,
                            DOA_APPOINTMENT_MASTER.END_TIME,
                            DOA_APPOINTMENT_MASTER.COMMENT,
                            DOA_APPOINTMENT_MASTER.INTERNAL_COMMENT,
                            DOA_APPOINTMENT_MASTER.IMAGE,
                            DOA_APPOINTMENT_MASTER.VIDEO,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            APT_ENR.ENROLLMENT_NAME AS APT_ENR_NAME,
                            APT_ENR.ENROLLMENT_ID AS APT_ENR_ID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_MASTER.IS_CHARGED,
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_SCHEDULING_CODE.SCHEDULING_CODE,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            DOA_SCHEDULING_CODE.UNIT,
                            SERVICE_PROVIDER.FIRST_NAME,
                            SERVICE_PROVIDER.LAST_NAME,
                            GROUP_CONCAT(DISTINCT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME)) SEPARATOR ', ') AS SERVICE_PROVIDER_NAME,
                            GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ', ') AS CUSTOMER_NAME,
                            DOA_LOCATION.LOCATION_NAME
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                        
                        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                                
                        LEFT JOIN DOA_APPOINTMENT_ENROLLMENT ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_ENROLLMENT.PK_APPOINTMENT_MASTER AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP'
                        LEFT JOIN DOA_ENROLLMENT_MASTER AS APT_ENR ON DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_MASTER = APT_ENR.PK_ENROLLMENT_MASTER AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP'
                                
                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'NORMAL'
                                
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_APPOINTMENT_MASTER.PK_LOCATION = DOA_LOCATION.PK_LOCATION
                        WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                        AND DOA_APPOINTMENT_MASTER.STATUS = 'A'
                        AND DOA_USER_MASTER.PK_USER_MASTER = $PK_USER_MASTER
                        AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('NORMAL', 'AD-HOC', 'GROUP')
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC");

    $has_appointments = $appointments->RecordCount() > 0;
    ?>
    <div class="tab-pane fade" id="Appointment1" role="tabpanel">
        <div class="appointment_area p-3">
            <?php if ($has_appointments): ?>
                <?php while (!$appointments->EOF):
                    $appointment = $appointments->fields;
                    $appointment_id = $appointment['PK_APPOINTMENT_MASTER'];
                    $service_name = $appointment['SERVICE_NAME'] ?: 'Private Lesson';
                    $service_code = $appointment['SERVICE_CODE'];
                    $scheduling_code = $appointment['SCHEDULING_CODE'];
                    $instructor_name = $appointment['FIRST_NAME'] . ' ' . $appointment['LAST_NAME'];
                    $location_name = $appointment['LOCATION_NAME'] ?: 'Studio Location';
                    $appointment_date = $appointment['DATE'];
                    $start_time = $appointment['START_TIME'];
                    $end_time = $appointment['END_TIME'];
                    $status = $appointment['APPOINTMENT_STATUS'];
                    $session_type = $appointment['SCHEDULING_CODE']; // Assuming this field exists
                    $appointment_color = $appointment['APPOINTMENT_COLOR'] ?: '#000000';

                    // Format date and time
                    $formatted_date = date('l F j', strtotime($appointment_date));
                    $time_range = date('g:i A', strtotime($start_time)) . ' - ' . date('g:i A', strtotime($end_time));

                    // Determine badge color based on session type
                    $badge_class = '';
                    $badge_text = '';
                    if ($session_type == 'GROUP' || $service_code == 'GRP') {
                        $badge_class = 'badge border';
                        $badge_style = 'background-color: #ffeaf4; color: #f573b6;';
                        $badge_text = 'GRP';
                    } else {
                        $badge_class = 'badge border';
                        $badge_style = 'background-color: #eeebff; color: #8c75e7;';
                        $badge_text = $appointment['SCHEDULING_CODE'];
                    }
                ?>
                    <div class="form-check border rounded-2 p-2 mb-2">
                        <div class="d-flex">
                            <span class="checkicon d-inline-flex me-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 511.999 511.999" style="enable-background:new 0 0 511.999 511.999;" xml:space="preserve" width="12px" height="12px" fill="#1FC16B">
                                    <!-- Green check icon SVG -->
                                    <path d="M511.923,416.776l-7.748-62.787c-0.002-0.013-0.003-0.026-0.005-0.04c-2.064-16.192-12.655-30.269-27.642-36.737 l-58.468-25.236c-0.061-0.026-0.124-0.044-0.185-0.07c-0.054-0.022-0.104-0.05-0.159-0.072l-4.873-1.892v-11.023 c13.391-10.093,22.29-26.088,24.403-44.179c3.314-2.211,5.64-5.854,6.028-10.139l2.18-24.998c0.335-3.628-0.769-7.165-3.107-9.958 c-0.716-0.855-1.521-1.609-2.397-2.252l0.028-0.509c0.01-0.184,0.015-0.368,0.015-0.551c0-32.516-28.132-58.97-62.711-58.97 c-20.668,0-39.026,9.456-50.457,24.005l0.016-0.3c0.01-0.183,0.015-0.367,0.015-0.551c0-36.69-31.788-66.54-70.859-66.54 c-39.07,0-70.856,29.85-70.856,66.54c0,0.185,0.005,0.37,0.015,0.554l0.016,0.298c-11.431-14.549-29.791-24.006-50.46-24.006 c-34.578,0-62.708,26.454-62.708,58.97c0,0.185,0.005,0.37,0.016,0.555l0.027,0.495c-0.876,0.642-1.681,1.393-2.397,2.246 c-2.34,2.787-3.451,6.318-3.129,9.928l2.191,25.046c0.317,3.631,2.03,6.918,4.825,9.255c0.393,0.329,0.8,0.633,1.221,0.912 c2.13,18.083,11.027,34.065,24.398,44.15v11.022l-4.874,1.893c-0.053,0.021-0.102,0.048-0.154,0.069 c-0.063,0.026-0.127,0.044-0.189,0.071l-58.463,25.236c-14.984,6.467-25.575,20.542-27.645,36.77l-7.756,62.791 c-0.351,2.844,0.535,5.702,2.432,7.849C4.403,426.77,7.131,428,9.996,428l101.71,0.022c0,0,0.002,0,0.003,0h288.58 c0.001,0,0.002,0,0.002,0L502.001,428c2.865,0,5.592-1.23,7.49-3.377C511.388,422.477,512.274,419.619,511.923,416.776z" />
                                </svg>
                            </span>
                            <label class="form-check-label"><?php echo htmlspecialchars($service_name); ?></label>

                            <?php if ($badge_text): ?>
                                <span class="badge <?php echo $badge_class; ?> ms-auto" style="<?php echo $badge_style; ?>"><?php echo $badge_text; ?></span>
                            <?php endif; ?>
                            <span class="badge bg-light fw-normal theme-text-light ms-1" style="background-color: <?php echo $appointment_color; ?>20 !important; color: <?php echo $nextLessonColor; ?> !important;">
                                <?php echo htmlspecialchars($status); ?>
                            </span>

                            <!-- You might want to add session count here if available -->
                            <!-- <span class="badge bg-light fw-normal theme-text-light ms-1">1 of 4</span> -->
                        </div>
                        <div class="statusareatext f12 theme-text-light" style="margin-left: 27px;">
                            <span class="text-uppercase"><?php echo $formatted_date . ', ' . $time_range . ' (PST)'; ?></span>
                            <ul class="list-inline mb-0 mt-1">
                                <?php if ($instructor_name): ?>
                                    <li class="list-inline-item fw-semibold">
                                        <?php
                                        // Get initials for badge
                                        $initials = '';
                                        if ($appointment['FIRST_NAME']) $initials .= substr($appointment['FIRST_NAME'], 0, 1);
                                        if ($appointment['LAST_NAME']) $initials .= substr($appointment['LAST_NAME'], 0, 1);
                                        ?>
                                        <span class="namebadge badge badgeprimary badge-pill px-1"><?php echo $initials; ?></span>
                                        <span class="name"><?php echo htmlspecialchars($instructor_name); ?></span>
                                    </li>
                                <?php endif; ?>
                                <li class="list-inline-item fw-semibold">
                                    <span class="badge rounded-pill bg-secondary p-1 d-inline-block me-1"></span>
                                    <span class="name"><?php echo htmlspecialchars($location_name); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php
                    $appointments->MoveNext();
                endwhile; ?>
            <?php else: ?>
                <div class="text-center py-4">
                    <p class="theme-text-light">No upcoming appointments found.</p>
                </div>
            <?php endif; ?>

            <button type="button" class="btn-secondary w-100 m-1" onclick="loadCreateAppointmentModal('<?php echo $PK_USER_MASTER; ?>')">Add Appointment</button>
        </div>
    </div>
    <?php
    $customer_details = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = '$PK_USER_MASTER' AND `IS_PRIMARY` = 1");
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
    ?>
    <div class="tab-pane fade" id="Family" role="tabpanel">
        <div class="family_area p-3">
            <?php
            $family_member_details = $db_account->Execute("SELECT DOA_CUSTOMER_DETAILS.*, DOA_RELATIONSHIP.RELATIONSHIP FROM DOA_CUSTOMER_DETAILS LEFT JOIN $master_database.DOA_RELATIONSHIP AS DOA_RELATIONSHIP ON DOA_CUSTOMER_DETAILS.PK_RELATIONSHIP = DOA_RELATIONSHIP.PK_RELATIONSHIP WHERE DOA_CUSTOMER_DETAILS.PK_CUSTOMER_PRIMARY = '$PK_CUSTOMER_DETAILS' AND DOA_CUSTOMER_DETAILS.IS_PRIMARY = 0");
            if ($PK_CUSTOMER_DETAILS > 0 && $family_member_details->RecordCount() > 0) {
                while (!$family_member_details->EOF) { ?>
                    <div class="form-check border rounded-2 p-2 mb-2">
                        <div class="d-flex align-items-center">
                            <label class="form-check-label"><?php echo $family_member_details->fields['FIRST_NAME'] . ' ' . $family_member_details->fields['LAST_NAME']; ?></label>
                            <button type="button" class="bg-transparent p-0 border-0 ms-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="-14 0 511 512" width="14px" height="14px" fill="CurrentColor">
                                    <path d="m.5 481.992188h300.078125v30.007812h-300.078125zm0 0" />
                                    <path d="m330.585938 481.992188h120.03125v30.007812h-120.03125zm0 0" />
                                    <path d="m483.464844 84.882812-84.867188-84.8710932-.011718-.0117188c-5.875 5.882812-313.644532 314.078125-313.75 314.183594l-57.59375 142.460937 142.46875-57.597656s181.703124-181.636719 313.753906-314.164063zm-42.421875.011719-35.917969 35.964844-42.375-42.371094 35.875-36.011719zm-99.46875 14.851563 42.34375 42.347656-21.199219 21.226562-42.320312-42.320312zm-238.554688 249.523437 31.597657 31.597657-53.042969 21.441406zm58.226563 15.789063-42.429688-42.433594 180.265625-180.503906 42.433594 42.433594zm0 0" />
                                </svg>
                            </button>
                        </div>
                        <div class="statusareatext f12 theme-text-light">
                            <span class="text-uppercase"><?php echo $family_member_details->fields['RELATIONSHIP']; ?></span>
                            <ul class="list-inline mb-0">
                                <li class="list-inline-item d-inline-flex gap-2">
                                    <a href="mailto:<?php echo $family_member_details->fields['EMAIL']; ?>"><?php echo $family_member_details->fields['EMAIL']; ?></a>
                                    <button type="button" class="bg-transparent p-0 border-0 ms-auto">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="14px" height="14px" fill="CurrentColor">
                                            <path d="m24.13 2.19h-12.59a2.86 2.86 0 0 0 -2.86 2.86v1.1h-1.43a3 3 0 0 0 -3 3v17.91a3 3 0 0 0 3 3h12.32a3 3 0 0 0 3-3v-1h1.56a2.87 2.87 0 0 0 2.87-2.82v-18.19a2.87 2.87 0 0 0 -2.87-2.86zm-3.56 24.87a1 1 0 0 1 -1 1h-12.32a1 1 0 0 1 -1-1v-17.91a1 1 0 0 1 1-1h12.32a1 1 0 0 1 1 1zm4.43-3.82a.87.87 0 0 1 -.87.86h-1.56v-14.95a3 3 0 0 0 -3-3h-8.89v-1.1a.86.86 0 0 1 .86-.86h12.59a.86.86 0 0 1 .87.86z" />
                                        </svg>
                                    </button>
                                </li>
                                <li class="list-inline-item d-inline-flex gap-2">
                                    <a href="tel:<?php echo $family_member_details->fields['PHONE']; ?>"><?php echo $family_member_details->fields['PHONE']; ?></a>
                                    <button type="button" class="bg-transparent p-0 border-0 ms-auto">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="14px" height="14px" fill="CurrentColor">
                                            <path d="m24.13 2.19h-12.59a2.86 2.86 0 0 0 -2.86 2.86v1.1h-1.43a3 3 0 0 0 -3 3v17.91a3 3 0 0 0 3 3h12.32a3 3 0 0 0 3-3v-1h1.56a2.87 2.87 0 0 0 2.87-2.82v-18.19a2.87 2.87 0 0 0 -2.87-2.86zm-3.56 24.87a1 1 0 0 1 -1 1h-12.32a1 1 0 0 1 -1-1v-17.91a1 1 0 0 1 1-1h12.32a1 1 0 0 1 1 1zm4.43-3.82a.87.87 0 0 1 -.87.86h-1.56v-14.95a3 3 0 0 0 -3-3h-8.89v-1.1a.86.86 0 0 1 .86-.86h12.59a.86.86 0 0 1 .87.86z" />
                                        </svg>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
            <?php $family_member_details->MoveNext();
                }
            }
            ?>
            <button type="button" class="btn-secondary w-100 m-1">Add Family</button>
        </div>
    </div>

    <div class="tab-pane fade" id="Payment" role="tabpanel">
        <div class="paymentarea p-3">
            <div class="searchbar-area position-relative mb-3">
                <input type="text" class="form-control" placeholder="Search..." style="padding-left: 35px;" />
                <span class="position-absolute" style="top: 7px; left: 10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M6.75 0C10.476 0 13.5 3.024 13.5 6.75C13.5 10.476 10.476 13.5 6.75 13.5C3.024 13.5 0 10.476 0 6.75C0 3.024 3.024 0 6.75 0ZM6.75 12C9.65025 12 12 9.65025 12 6.75C12 3.849 9.65025 1.5 6.75 1.5C3.849 1.5 1.5 3.849 1.5 6.75C1.5 9.65025 3.849 12 6.75 12ZM13.1137 12.0532L15.2355 14.1742L14.1742 15.2355L12.0532 13.1137L13.1137 12.0532Z" fill="#99A0AE"></path>
                    </svg>
                </span>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Enrollment</th>
                            <th style="text-align: center;">Due</th>
                            <th style="text-align: center;">Amount</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $payment_due = $db_account->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.MISC_ID, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME FROM DOA_ENROLLMENT_LEDGER INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_LEDGER.TRANSACTION_TYPE = 'Billing' AND DOA_ENROLLMENT_LEDGER.IS_PAID = 0 AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $PK_USER_MASTER ORDER BY DOA_ENROLLMENT_LEDGER.DUE_DATE DESC");
                        if ($payment_due->RecordCount() > 0) {
                            while (!$payment_due->EOF) {
                                $name = $payment_due->fields['ENROLLMENT_NAME'];
                                $ENROLLMENT_ID = $payment_due->fields['ENROLLMENT_ID'];
                                if (empty($name)) {
                                    $enrollment_name = '';
                                } else {
                                    $enrollment_name = "$name" . " - ";
                                } ?>
                                <tr>
                                    <td><?= ($enrollment_name . $ENROLLMENT_ID == null) ? $enrollment_name . $payment_due->fields['MISC_ID'] : $enrollment_name . $ENROLLMENT_ID ?></td>
                                    <td><?= date('m/d/Y', strtotime($payment_due->fields['DUE_DATE'])) ?></td>
                                    <td>$<?= number_format($payment_due->fields['BILLED_AMOUNT'], 2) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="payNow(<?= $payment_due->fields['PK_ENROLLMENT_MASTER'] ?>, <?= $payment_due->fields['PK_ENROLLMENT_LEDGER'] ?>, <?= $payment_due->fields['BILLED_AMOUNT'] ?>, '<?= $ENROLLMENT_ID ?>');">Pay Now</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="editDueDate(<?= $payment_due->fields['PK_ENROLLMENT_LEDGER'] ?>, '<?= date('m/d/Y', strtotime($payment_due->fields['DUE_DATE'])) ?>', 'billing')">Edit Date</button>
                                    </td>
                                </tr>
                        <?php $payment_due->MoveNext();
                            }
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function editDueDate(PK_ENROLLMENT_LEDGER, DUE_DATE, TYPE) {
        $('#PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#old_due_date').val(DUE_DATE);
        $('#due_date').val(DUE_DATE);
        $('#edit_type').val(TYPE);
        $('#billing_due_date_model').modal('show');
    }
</script>




<!-- End Customer Details -->