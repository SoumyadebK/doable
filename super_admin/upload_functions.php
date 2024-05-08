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

function getAllEnrollments() {
    global $db1;
    return $db1->Execute("SELECT * FROM enrollment");
}

function getAllEnrollmentServices() {
    global $db1;
    return $db1->Execute("SELECT * FROM enrollment_services");
}

function getAllEnrollmentPayments() {
    global $db1;
    return $db1->Execute("SELECT * FROM payments");
}

function getAllEvents() {
    global $db1;
    return $db1->Execute("SELECT * FROM general_appt");
}

function getAllAppointments() {
    global $db1;
    return $db1->Execute("SELECT * FROM service_appt");
}

function getAllStudentIds($service_appt_id) {
    global $db1;
    return $db1->Execute("SELECT student_id FROM service_appt WHERE id = '$service_appt_id'");
}

function getRole($role_id){
    if ($role_id > 0) {
        global $db1;
        $role = $db1->Execute("SELECT name FROM roles WHERE id = '$role_id'");
        if ($role->fields['name'] == "Supervisor" || $role->fields['name'] == "Counselor") {
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

function getEnrollmentType($enrollmentTypeId) {
    global $db1;
    $enrollmentTypeData = $db1->Execute("SELECT enrollment_type, code FROM enrollment_type WHERE enrollment_type_id = '$enrollmentTypeId'");
    return [$enrollmentTypeData->fields['enrollment_type'], $enrollmentTypeData->fields['code']];
}

function getEnrollmentDetails($enrollment_id) {
    global $db1;
    $enrollment_details = $db1->Execute("SELECT total_cost, discount, fincharge FROM enrollment WHERE enrollment_id = '$enrollment_id'");
    return [$enrollment_details->fields['total_cost'], $enrollment_details->fields['discount'], $enrollment_details->fields['fincharge']];
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

//function getName($customer_id) {
//    global $db1;
//    $name_taker = $db1->Execute("SELECT first_name, last_name FROM customer WHERE customer_id = '$customer_id'");
//    return [$name_taker->fields['first_name'], $name_taker->fields['last_name']];
//}
