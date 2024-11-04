<?php
require_once('../global/query_factory.php');
require_once('../global/common_functions.php');

$db1 = new queryFactory();
if($_SERVER['HTTP_HOST'] == 'localhost' ) {
    $conn1 = $db1->connect('localhost','root','',$_SESSION['MIGRATION_DB_NAME']);
    $http_path = 'http://localhost/doable/';
} else {
    $conn1 = $db1->connect('localhost','root','b54eawxj5h8ev',$_SESSION['MIGRATION_DB_NAME']);
    $http_path = 'http://allonehub.com/';
}
if ($db1->error_number){
    die("Connection Error");
}
function getStartTime()
{
    global $db1;
    return $db1->Execute("SELECT * FROM settings WHERE name = 'Calendar Start Time'");
}
function getEndTime()
{
    global $db1;
    return $db1->Execute("SELECT * FROM settings WHERE name = 'Calendar End Time'");
}
function getAllInquiryMethod() {
    global $db1;
    return $db1->Execute("SELECT * FROM inquiry_type");
}

function getAllUsers() {
    global $db1;
    return $db1->Execute("SELECT * FROM users");
}

function getAllCustomers() {
    global $db1;
    return $db1->Execute("SELECT * FROM customer");
}

function getAllServices() {
    global $db1;
    return $db1->Execute("SELECT * FROM service_codes");
}

function getAllSchedulingCodes() {
    global $db1;
    return $db1->Execute("SELECT * FROM booking_codes");
}

function getAllEnrollmentTypes() {
    global $db1;
    return $db1->Execute("SELECT * FROM enrollment_type");
}

function getAllPackages() {
    global $db1;
    return $db1->Execute("SELECT * FROM packages");
}

function getPackageServices($package_id) {
    global $db1;
    return $db1->Execute("SELECT * FROM package_services WHERE package_id = '$package_id'");
}

function getAllEnrollments() {
    global $db1;
    return $db1->Execute("SELECT * FROM enrollment WHERE `enrollmentname` NOT LIKE '%Renewal (NO SALE)%'");
}

function getAllEnrollmentServices() {
    global $db1;
    return $db1->Execute("SELECT * FROM enrollment_services");
}

function getAllEnrollmentServicesById($enrollment_id) {
    global $db1;
    return $db1->Execute("SELECT * FROM enrollment_services WHERE enrollment_id = '$enrollment_id'");
}

function getAllEnrollmentChargesById($enrollment_id) {
    global $db1;
    return $db1->Execute("SELECT * FROM `charges` WHERE `enroll_id` = '$enrollment_id'");
}

function getAllEnrollmentPaymentByChargeId($charge_id) {
    global $db1;
    return $db1->Execute("SELECT * FROM `payments` WHERE charge_id = '$charge_id'");
}

function getAllEnrollmentPayments() {
    global $db1;
    return $db1->Execute("SELECT * FROM payments");
}

function getAllGeneralAppt() {
    global $db1;
    return $db1->Execute("SELECT * FROM general_appt");
}

function getAllPrivateAppointments() {
    global $db1;
    return $db1->Execute("SELECT * FROM service_appt WHERE `service_id` LIKE '%PRI%' ORDER BY appt_date ASC, appt_time ASC");
}

function getAllPrivateAppointmentsByCustomerId($customer_id) {
    global $db1;
    return $db1->Execute("SELECT * FROM service_appt WHERE student_id = '$customer_id' AND `service_id` LIKE '%PRI%' ORDER BY appt_date ASC, appt_time ASC");
}

function getAllGroupAppointments() {
    global $db1;
    return $db1->Execute("SELECT * FROM service_appt WHERE `service_id` NOT LIKE '%PRI%' AND `service_id` NOT LIKE '%COMM%' ORDER BY appt_date ASC, appt_time ASC");
}

function getDemoAppointments() {
    global $db1;
    return $db1->Execute("SELECT * FROM service_appt WHERE `service_id` LIKE '%COMM%' ORDER BY appt_date ASC, appt_time ASC");
}

function getAllStudentIds($service_appt_id) {
    global $db1;
    return $db1->Execute("SELECT student_id, payment_status FROM group_parties WHERE service_appt_id = '$service_appt_id'");
}

function getRole($role_id){
    if ($role_id > 0) {
        global $db1;
        $role = $db1->Execute("SELECT name FROM roles WHERE id = '$role_id'");
        if ( $role->fields['name'] == "Counselor") {
            return "Account User";
        } elseif ($role->fields['name'] == "Instructor") {
            return "Service Provider";
        } else {
            return $role->fields['name'];
        }
    } elseif ($role_id == 0) {
        return "Service Provider";
    } else {
        return 'Guest';
    }
}

function getInquiry($inquiry_id) {
    global $db1;
    $inquiry = $db1->Execute("SELECT inquiry_type FROM inquiry_type WHERE inquiry_id = '$inquiry_id'");
    if ($inquiry->RecordCount() > 0) {
        return $inquiry->fields['inquiry_type'];
    } else {
        return '';
    }
}

function getTaker($taker_id) {
    global $db1;
    $inquiry_taker = $db1->Execute("SELECT user_name FROM users WHERE user_id = '$taker_id'");
    if ($inquiry_taker->RecordCount() > 0) {
        return $inquiry_taker->fields['user_name'];
    } else {
        return '';
    }
}

function getService($service_id) {
    global $db1;
    $service_taker = $db1->Execute("SELECT service_name, chargeable FROM service_codes WHERE service_id = '$service_id'");
    return [$service_taker->fields['service_name'], $service_taker->fields['chargeable']];
}

function getCustomer($customer_id) {
    global $db1;
    $customer_taker = $db1->Execute("SELECT email FROM customer WHERE customer_id = '$customer_id'");
    if ($customer_taker->RecordCount() > 0) {
        return $customer_taker->fields['email'];
    } else {
        return 0;
    }
}

function getServiceMaster($service_id) {
    global $db1;
    $service_name_taker = $db1->Execute("SELECT service_name FROM service_codes WHERE service_id = '$service_id'");
    if ($service_name_taker->RecordCount() > 0) {
        return $service_name_taker->fields['service_name'];
    } else {
        return 0;
    }
}

function getServiceCode($service_id) {
    global $db1;
    $service_name_taker = $db1->Execute("SELECT service_id FROM service_codes WHERE service_id = '$service_id'");
    if ($service_name_taker->RecordCount() > 0) {
        return $service_name_taker->fields['service_id'];
    } else {
        return 0;
    }
}

function getBookingCode($booking_code) {
    global $db1;
    $result = $db1->Execute("SELECT booking_name FROM booking_codes WHERE booking_code = '$booking_code'");
    if ($result->RecordCount() > 0) {
        return $result->fields['booking_name'];
    } else {
        return 0;
    }
}

function getEnrollmentType($enrollmentTypeId): array
{
    global $db1;
    $enrollmentTypeData = $db1->Execute("SELECT enrollment_type, code FROM enrollment_type WHERE enrollment_type_id = '$enrollmentTypeId'");
    return [$enrollmentTypeData->fields['enrollment_type'], $enrollmentTypeData->fields['code']];
}

function getUser($user_id) {
    global $db1;
    $customer_taker = $db1->Execute("SELECT email FROM users WHERE user_id = '$user_id'");
    if ($customer_taker->RecordCount() > 0) {
        return $customer_taker->fields['email'];
    } else {
        return 0;
    }
}
function getEnrollmentDetails($enrollment_id) {
    global $db1;
    $enrollment_details = $db1->Execute("SELECT sale_value, discount, total_cost FROM enrollment WHERE enrollment_id = '$enrollment_id'");
    return [$enrollment_details->fields['sale_value'], $enrollment_details->fields['discount'], $enrollment_details->fields['total_cost']];
}

function getPackageCode($package_id) {
    global $db1;
    $package_data = $db1->Execute("SELECT package_name FROM packages WHERE package_id = '$package_id'");
    if ($package_data->RecordCount() > 0) {
        return $package_data->fields['package_name'];
    } else {
        return 0;
    }
}

//function getName($customer_id) {
//    global $db1;
//    $name_taker = $db1->Execute("SELECT first_name, last_name FROM customer WHERE customer_id = '$customer_id'");
//    return [$name_taker->fields['first_name'], $name_taker->fields['last_name']];
//}
