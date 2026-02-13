<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$title = "SEMI ANNUAL STUDENT INVENTORY REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date'])); // Changed from +6 day to +6 months for semi-annual

$week_number = $_GET['week_number'];
$YEAR = date('Y', strtotime($from_date));

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';

$location_name = '';
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$resultsArray = [];
while (!$results->EOF) {
    $resultsArray[] = $results->fields['LOCATION_NAME'];
    $results->MoveNext();
}
$totalResults = count($resultsArray);
$concatenatedResults = "";
foreach ($resultsArray as $key => $result) {
    $concatenatedResults .= $result;
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}

// Fixed SQL Query for Semi-Annual Student Inventory Report
$report_sql = "
SELECT
    us.PK_USER,
    um.PK_USER_MASTER,
    CONCAT(us.LAST_NAME, ', ', us.FIRST_NAME) AS STUDENT_NAME,
    us.ADDRESS AS COMPLETE_ADDRESS,

    /* LAST COMPLETED LESSON */
    (
        SELECT MAX(am.DATE)
        FROM DOA_APPOINTMENT_MASTER am
        JOIN DOA_ENROLLMENT_MASTER em5
            ON em5.PK_ENROLLMENT_MASTER = am.PK_ENROLLMENT_MASTER
        WHERE em5.PK_USER_MASTER = em.PK_USER_MASTER
          AND am.IS_CHARGED = 1
          AND am.DATE <= CURDATE()
    ) AS LAST_LESSON_DATE,

    /* NEXT FUTURE LESSON */
    (
        SELECT MIN(am.DATE)
        FROM DOA_APPOINTMENT_MASTER am
        JOIN DOA_ENROLLMENT_MASTER em6
            ON em6.PK_ENROLLMENT_MASTER = am.PK_ENROLLMENT_MASTER
        WHERE em6.PK_USER_MASTER = em.PK_USER_MASTER
          AND am.DATE > CURDATE()
          AND am.IS_CHARGED = 0
    ) AS NEXT_LESSON_DATE

FROM DOA_ENROLLMENT_MASTER em
JOIN DOA_MASTER.DOA_USER_MASTER um
    ON um.PK_USER_MASTER = em.PK_USER_MASTER
JOIN DOA_MASTER.DOA_USERS us
    ON us.PK_USER = um.PK_USER

WHERE em.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
  AND em.ACTIVE = 1
  AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
  AND us.ACTIVE = 1
  AND us.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'
  AND us.IS_DELETED = 0

GROUP BY us.PK_USER
ORDER BY NEXT_LESSON_DATE DESC
";


function getMiscItemsByUser($pk_user_master, $from_date, $to_date, $db_account)
{
    $sql = "
        SELECT es.SERVICE_DETAILS, em.ENROLLMENT_NAME, es.FINAL_AMOUNT
        FROM DOA_ENROLLMENT_SERVICE es
        JOIN DOA_ENROLLMENT_MASTER em
            ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER
        JOIN DOA_SERVICE_MASTER sm
            ON sm.PK_SERVICE_MASTER = es.PK_SERVICE_MASTER
        WHERE em.PK_USER_MASTER = $pk_user_master
          AND sm.PK_SERVICE_CLASS = '5' /* Miscellaneous Services */
          AND es.FINAL_AMOUNT > 0
          AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
    ";

    $misc = $db_account->Execute($sql);

    if ($misc->RecordCount() == 0) {
        return '';
    }

    $out = [];
    while (!$misc->EOF) {
        // Get the appropriate name for EACH row
        if (!empty($misc->fields['ENROLLMENT_NAME'])) {
            $enrollment_name = $misc->fields['ENROLLMENT_NAME'];
        } elseif (!empty($misc->fields['SERVICE_DETAILS'])) {
            $enrollment_name = $misc->fields['SERVICE_DETAILS'];
        } else {
            $enrollment_name = 'Miscellaneous Item';
        }

        $out[] = htmlspecialchars($enrollment_name) .
            ' - $' . number_format($misc->fields['FINAL_AMOUNT'], 2);
        $misc->MoveNext();
    }

    return implode('<br>', $out);
}


// Handle form submissions
if (!empty($_GET['WEEK_NUMBER'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = $_GET['NAME'] ?? 'semi_annual_student_inventory_report';

    // Extract week number from "Week Number X" format
    $week_parts = explode(' ', $_GET['WEEK_NUMBER']);
    $WEEK_NUMBER = end($week_parts);

    // Calculate start date from week number
    $year = date('Y');
    $date = new DateTime();
    $date->setISODate($year, $WEEK_NUMBER);
    $date->modify('-1 day');

    $START_DATE = $date->format('Y-m-d');

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&report_type=' . $report_name);
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&report_type=' . $report_name);
    } else {
        header('location:semi_annual_student_inventory_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&type=' . $type);
    }
    exit;
}

if (!empty($_GET['START_DATE'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = 'semi_annual_student_inventory_report';
    $WEEK_NUMBER = explode(' ', $_GET['WEEK_NUMBER'])[2];
    $START_DATE = date('Y-m-d', strtotime($_GET['START_DATE']));
    $END_DATE = date('Y-m-d', strtotime($_GET['END_DATE']));

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name);
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&PK_USER=' . implode(',', $PK_USER));
    } else {
        header('location:semi_annual_student_inventory_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">

        <div class="page-wrapper" style="padding-top: 0px !important;">

            <div class="container-fluid body_content" style="margin-top: 0px;">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                                <li class="breadcrumb-item active"><a href="semi_annual_student_inventory_report.php"><?= $title ?></a></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <?php
                if ($type != 'export') { ?>
                    <div class="row">
                        <div class="col-12 align-self-center">
                            <div class="card">
                                <div class="card-body" style="padding-bottom: 0px !important;">
                                    <form class="form-material form-horizontal" action="" method="get" id="reportForm">
                                        <!-- <input type="hidden" name="start_date" id="start_date">
                                        <input type="hidden" name="end_date" id="end_date"> -->
                                        <input type="hidden" name="NAME" id="NAME" value="semi_annual_student_inventory_report">
                                        <div class="row justify-content-start">
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <input type="text" id="START_DATE" name="START_DATE" class="form-control" placeholder="Start Date" value="<?= !empty($_GET['start_date']) ? date('m/d/Y', strtotime($_GET['start_date'])) : '' ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <input type="text" id="END_DATE" name="END_DATE" class="form-control" placeholder="End Date" value="<?= !empty($_GET['end_date']) ? date('m/d/Y', strtotime($_GET['end_date'])) : '' ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <?php if (in_array('Reports Create', $PERMISSION_ARRAY)) { ?>
                                                    <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                                    <input type="submit" name="generate_pdf" value="Generate PDF" class="btn btn-info" style="background-color: #39B54A !important;">
                                                    <!-- <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info" style="background-color: #39B54A !important;"> -->
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

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
                                                <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="5"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= $concatenatedResults ?></th>
                                                <th style="width:50%; text-align: center; font-weight: bold" colspan="4"><?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?></th>
                                            </tr>
                                            <tr>
                                                <th style="width:15%; text-align: center; vertical-align:auto;" rowspan="2">Full Name of Student (Last, First)</th>
                                                <th style="width:10%; text-align: center" rowspan="2">Complete Address</th>
                                                <th style="width:10%; text-align: center" colspan="3">Private Lessons (45-Minute)</th>
                                                <th style="width:15%; text-align: center" rowspan="2">Misc Serv. <br>Type & Value</th>
                                                <th style="width:8%; text-align: center" rowspan="2">Total <br>$ Due</th>
                                                <th style="width:8%; text-align: center" rowspan="2">Date of Last <br>Lesson Taught</th>
                                                <th style="width:8%; text-align: center" rowspan="2">Date of Next <br>Future Lesson</th>
                                            </tr>
                                            <tr>
                                                <th style="width:6%; text-align: center">Total Enrolled</th>
                                                <th style="width:6%; text-align: center">Total Used</th>
                                                <th style="width:6%; text-align: center">Remaining</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $data = $db_account->Execute($report_sql);
                                            while (!$data->EOF) {
                                                $misc_html = getMiscItemsByUser(
                                                    $data->fields['PK_USER_MASTER'],
                                                    $from_date,
                                                    $to_date,
                                                    $db_account
                                                );
                                            ?>
                                                <tr>
                                                    <td><?= $data->fields['STUDENT_NAME'] ?></td>
                                                    <td><?= $data->fields['COMPLETE_ADDRESS'] ?></td>
                                                    <?php
                                                    $pending_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_MASTER.CHARGE_TYPE, DOA_ENROLLMENT_MASTER.PK_USER_MASTER FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') AND DOA_SERVICE_CODE.SERVICE_CODE LIKE '%PRI%' AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $data->fields['PK_USER_MASTER']);
                                                    $pending_service_code_array = [];
                                                    while (!$pending_service_data->EOF) {
                                                        if ($pending_service_data->fields['CHARGE_TYPE'] == 'Membership') {
                                                            $NUMBER_OF_SESSION = getSessionCreatedCount($pending_service_data->fields['PK_ENROLLMENT_SERVICE']);
                                                        } else {
                                                            $NUMBER_OF_SESSION = $pending_service_data->fields['NUMBER_OF_SESSION'];
                                                        }
                                                        $SESSION_SCHEDULED = getSessionScheduledCount($pending_service_data->fields['PK_ENROLLMENT_SERVICE']);
                                                        $SESSION_COMPLETED = getSessionCompletedCount($pending_service_data->fields['PK_ENROLLMENT_SERVICE']);
                                                        $PRICE_PER_SESSION = $pending_service_data->fields['PRICE_PER_SESSION'];
                                                        $paid_session = ($PRICE_PER_SESSION > 0) ? number_format($pending_service_data->fields['TOTAL_AMOUNT_PAID'] / $PRICE_PER_SESSION, 2) : $NUMBER_OF_SESSION;
                                                        $remain_session = $NUMBER_OF_SESSION - ($SESSION_COMPLETED + $SESSION_SCHEDULED);
                                                        $ps_balance = $paid_session - $SESSION_COMPLETED;

                                                        //if ($remain_session > 0) {
                                                        if (isset($pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']])) {
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['CODE'] = $pending_service_data->fields['SERVICE_CODE'];
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['ENROLL'] += $NUMBER_OF_SESSION;
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['REMAIN'] += $remain_session;
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['PAID'] += $pending_service_data->fields['TOTAL_AMOUNT_PAID'];
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['TOTAL'] += $pending_service_data->fields['FINAL_AMOUNT'];

                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['USED'] += $SESSION_COMPLETED;
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['SCHEDULED'] += $SESSION_SCHEDULED;
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['BALANCE'] += $ps_balance;
                                                        } else {
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['CODE'] = $pending_service_data->fields['SERVICE_CODE'];
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['ENROLL'] = $NUMBER_OF_SESSION;
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['REMAIN'] = $remain_session;
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['PAID'] = $pending_service_data->fields['TOTAL_AMOUNT_PAID'];
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['TOTAL'] = $pending_service_data->fields['FINAL_AMOUNT'];
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['USED'] = $SESSION_COMPLETED;
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['SCHEDULED'] = $SESSION_SCHEDULED;
                                                            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['BALANCE'] = $ps_balance;
                                                        }
                                                        //}

                                                        $pending_service_data->MoveNext();
                                                    } ?>
                                                    <?php
                                                    $total_enrolled = 0;
                                                    $total_used = 0;
                                                    $total_remain = 0;
                                                    $total_paid = 0;
                                                    $total_final = 0;
                                                    foreach ($pending_service_code_array as $service_code) {
                                                        $total_enrolled += (float)$service_code['ENROLL'];
                                                        $total_used     += (float)$service_code['USED'];
                                                        $total_remain   = $total_enrolled - $total_used;
                                                        $total_paid     += (float)$service_code['PAID'];
                                                        $total_final    += (float)$service_code['TOTAL'];
                                                    }
                                                    $total_due = $total_final - $total_paid;
                                                    ?>

                                                    <td style="text-align: center;"><?= $total_enrolled ?></td>
                                                    <td style="text-align: center;"><?= $total_used ?></td>
                                                    <td style="text-align: center;"><?= $total_remain ?></td>
                                                    <td><?= $misc_html ?></td>
                                                    <td align="right">$<?= number_format($total_due, 2) ?></td>
                                                    <td style="text-align: center;"><?= !empty($data->fields['LAST_LESSON_DATE']) ? date('m/d/Y', strtotime($data->fields['LAST_LESSON_DATE'])) : '' ?></td>
                                                    <td style="text-align: center;"><?= !empty($data->fields['NEXT_LESSON_DATE']) ? date('m/d/Y', strtotime($data->fields['NEXT_LESSON_DATE'])) : ''  ?></td>
                                                </tr>
                                            <?php
                                                $data->MoveNext();
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
</body>

</html>
<script>
    $(function() {
        $(".datepicker-normal").datepicker({
            dateFormat: "mm/dd/yy",
            changeMonth: true,
            changeYear: true,
            yearRange: "2000:2035"
        });
    });
</script>

<script>
    $(document).ready(function() {
        $("#START_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(selected) {
                $("#END_DATE").datepicker("option", "minDate", selected);
                $("#START_DATE, #END_DATE").trigger("change");
            }
        });
        $("#END_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(selected) {
                $("#START_DATE").datepicker("option", "maxDate", selected)
            }
        });

        <?php if (!empty($week_number)): ?>
            $('#WEEK_NUMBER1').val("Week Number <?= $week_number ?>");
            $('#start_date').val("<?= $from_date ?>");
        <?php endif; ?>
    });
</script>