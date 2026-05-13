<?php
require_once('../global/config.php');
$title = "Customer Summary Report";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
} ?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">

        <div class="page-wrapper" style="padding-top: 0px !important;">


            <?php require_once('layout/report_menu.php') ?>
            <div class="container-fluid" style="padding: 10px 20px 0 20px; margin-top: 0px;">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div>
                                    <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                    <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                </div>

                                <div class="table-responsive">
                                    <table id="myTable" class="table table-bordered" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" rowspan="2">Student (Active)<br>
                                                    Department (All)</th>
                                                <th style="width:20%; text-align: center; font-weight: bold" rowspan="2">Teacher(s) (Split)</th>
                                                <th style="width:20%; text-align: center; font-weight: bold" colspan="3">Overall (PRIs)</th>
                                                <th style="width:30%; text-align: center; font-weight: bold" colspan="4">Open Balance Enrollments</th>
                                                <th style="width:10%; text-align: center; font-weight: bold" colspan="2">PaidAhead</th>
                                            </tr>
                                            <tr>
                                                <th style="width:10%; text-align: center; font-weight: bold">Enrolled</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">Used</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">Remaining</th>
                                                <th style="width:12%; text-align: center; font-weight: bold">Total Cost/PRIs</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">Lesson Avg</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">Balance</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">UnpaidPRI</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">PRIs</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">AMOUNT</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;
                                            $customers_with_data = [];

                                            $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USERS.ACTIVE=1 AND DOA_USERS.IS_DELETED=0 AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY FIRST_NAME");

                                            while (!$row->EOF) {
                                                $pk_user_master = $row->fields['PK_USER_MASTER'];
                                                $pk_user = $row->fields['PK_USER'];

                                                // Initialize totals
                                                $total_enrolled = 0;
                                                $total_used = 0;
                                                $total_remaining = 0;
                                                $total_cost = 0;
                                                $total_lesson_avg = 0;
                                                $total_balance = 0;
                                                $total_unpaid_pri = 0;
                                                $total_pri = 0;
                                                $total_amount = 0;

                                                // Array to store unique teacher names
                                                $teachers_array = [];

                                                // Get all active enrollments for this customer
                                                $enrollments = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_MASTER WHERE PK_USER_MASTER = " . $pk_user_master . " AND (STATUS = 'A' OR STATUS = 'CA')");

                                                while (!$enrollments->EOF) {
                                                    $pk_enrollment_master = $enrollments->fields['PK_ENROLLMENT_MASTER'];

                                                    // Get teachers for this enrollment from DOA_ENROLLMENT_SERVICE_PROVIDER
                                                    $teachers = $db_account->Execute("SELECT DISTINCT esp.SERVICE_PROVIDER_ID, CONCAT(u.FIRST_NAME, ' ', u.LAST_NAME) AS TEACHER_NAME 
                                                        FROM DOA_ENROLLMENT_SERVICE_PROVIDER esp 
                                                        LEFT JOIN " . $master_database . ".DOA_USERS u ON esp.SERVICE_PROVIDER_ID = u.PK_USER 
                                                        WHERE esp.PK_ENROLLMENT_MASTER = " . $pk_enrollment_master . " AND u.ACTIVE = 1 AND u.IS_DELETED = 0");

                                                    while (!$teachers->EOF) {
                                                        if (!empty($teachers->fields['TEACHER_NAME'])) {
                                                            $teachers_array[$teachers->fields['SERVICE_PROVIDER_ID']] = $teachers->fields['TEACHER_NAME'];
                                                        }
                                                        $teachers->MoveNext();
                                                    }

                                                    // Get service details for this enrollment
                                                    $services = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_MASTER.CHARGE_TYPE 
                                                        FROM DOA_ENROLLMENT_SERVICE 
                                                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER 
                                                        JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE 
                                                        WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $pk_enrollment_master);

                                                    while (!$services->EOF) {
                                                        // Calculate number of sessions
                                                        if ($services->fields['CHARGE_TYPE'] == 'Membership') {
                                                            $number_of_sessions = getSessionCreatedCount($services->fields['PK_ENROLLMENT_SERVICE']);
                                                        } else {
                                                            $number_of_sessions = $services->fields['NUMBER_OF_SESSION'];
                                                        }

                                                        $sessions_completed = getSessionCompletedCount($services->fields['PK_ENROLLMENT_SERVICE']);
                                                        $sessions_scheduled = getSessionScheduledCount($services->fields['PK_ENROLLMENT_SERVICE']);

                                                        $remaining_sessions = $number_of_sessions - ($sessions_completed + $sessions_scheduled);
                                                        $price_per_session = $services->fields['PRICE_PER_SESSION'];
                                                        $total_amount_paid = $services->fields['TOTAL_AMOUNT_PAID'];
                                                        $total_cost_services = $number_of_sessions * $price_per_session;

                                                        // Calculate paid sessions and balance
                                                        $paid_sessions = ($price_per_session > 0) ? $total_amount_paid / $price_per_session : $number_of_sessions;
                                                        $balance_sessions = $paid_sessions - $sessions_completed;

                                                        // Add to totals
                                                        $total_enrolled += $number_of_sessions;
                                                        $total_used += $sessions_completed;
                                                        $total_remaining += $remaining_sessions;
                                                        $total_cost += $total_cost_services;
                                                        $total_balance += $balance_sessions;

                                                        // Calculate lesson average (if there are used lessons)
                                                        if ($sessions_completed > 0) {
                                                            $total_lesson_avg += ($total_amount_paid / $sessions_completed);
                                                        }

                                                        // Unpaid PRI (negative balance sessions)
                                                        if ($balance_sessions < 0) {
                                                            $total_unpaid_pri += abs($balance_sessions);
                                                        }

                                                        // Total PRI (if price per session exists)
                                                        if ($price_per_session > 0) {
                                                            $total_pri += $balance_sessions;
                                                        }

                                                        $total_amount += $total_amount_paid;

                                                        $services->MoveNext();
                                                    }

                                                    $enrollments->MoveNext();
                                                }

                                                // Calculate lesson average properly
                                                $lesson_avg = ($total_used > 0) ? ($total_amount / $total_used) : 0;

                                                // Get teachers names as a comma-separated string
                                                $teachers_names = !empty($teachers_array) ? implode(', ', $teachers_array) : '-';

                                                // Check if customer has any non-zero values
                                                $has_data = ($total_enrolled != 0 || $total_used != 0 || $total_remaining != 0 ||
                                                    $total_cost != 0 || $lesson_avg != 0 || $total_balance != 0 ||
                                                    $total_unpaid_pri != 0 || $total_pri != 0 || $total_amount != 0);

                                                // Only add to array if customer has data
                                                if ($has_data) {
                                                    $customers_with_data[] = [
                                                        'name' => $row->fields['NAME'],
                                                        'teachers' => $teachers_names,
                                                        'pk_user' => $row->fields['PK_USER'],
                                                        'pk_user_master' => $row->fields['PK_USER_MASTER'],
                                                        'total_enrolled' => $total_enrolled,
                                                        'total_used' => $total_used,
                                                        'total_remaining' => $total_remaining,
                                                        'total_cost' => $total_cost,
                                                        'lesson_avg' => $lesson_avg,
                                                        'total_balance' => $total_balance,
                                                        'total_unpaid_pri' => $total_unpaid_pri,
                                                        'total_pri' => $total_pri,
                                                        'total_amount' => $total_amount
                                                    ];
                                                }

                                                $row->MoveNext();
                                                $i++;
                                            }

                                            // Display only customers with data
                                            foreach ($customers_with_data as $customer) {
                                            ?>
                                                <tr>
                                                    <td><?= $customer['name'] ?></td>
                                                    <td><?= $customer['teachers'] ?></td>
                                                    <td style="text-align: right"><?= number_format($customer['total_enrolled'], 2) ?></td>
                                                    <td style="text-align: right"><?= number_format($customer['total_used'], 2) ?></td>
                                                    <td style="text-align: right"><?= number_format($customer['total_remaining'], 2) ?></td>
                                                    <td style="text-align: right"><?= number_format($customer['total_cost'], 2) ?></td>
                                                    <td style="text-align: right"><?= number_format($customer['lesson_avg'], 2) ?></td>
                                                    <td style="text-align: right"><?= number_format($customer['total_balance'], 2) ?></td>
                                                    <td style="text-align: right"><?= number_format($customer['total_unpaid_pri'], 2) ?></td>
                                                    <td style="text-align: right"><?= number_format($customer['total_pri'], 2) ?></td>
                                                    <td style="text-align: right"><?= number_format($customer['total_amount'], 2) ?></td>
                                                </tr>
                                            <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>

    <script>
        function ConfirmDelete(anchor) {
            let conf = confirm("Are you sure you want to delete?");
            if (conf)
                window.location = anchor.attr("href");
        }

        function editpage(id, master_id) {
            window.location.href = "customer.php?id=" + id + "&master_id=" + master_id;
        }
    </script>



</body>

</html>