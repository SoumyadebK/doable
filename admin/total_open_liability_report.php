<?php
require_once('../global/config.php');
$title = "Total Open Liability Report";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$week_number = $_GET['week_number'];
$YEAR = date('Y');

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));

if (!empty($_GET['START_DATE'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = 'total_open_liability_report';
    $WEEK_NUMBER = explode(' ', $_GET['WEEK_NUMBER'])[2];
    $START_DATE = $_GET['START_DATE'];
    $END_DATE = $_GET['END_DATE'];

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name);
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name);
    } else {
        header('location:total_open_liability_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

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
                                <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                                <li class="breadcrumb-item active"><a href="customer_summary_report.php"><?= $title ?></a></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="row" style="padding: 20px 0px 0px 15px;">
                                <form class="form-material form-horizontal" action="" method="get">
                                    <input type="hidden" name="start_date" id="start_date">
                                    <input type="hidden" name="end_date" id="end_date">
                                    <div class="row">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" placeholder="Start Date" value="<?= !empty($_GET['start_date']) ? date('m/d/Y', strtotime($_GET['start_date'])) : '' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" placeholder="End Date" value="<?= !empty($_GET['end_date']) ? date('m/d/Y', strtotime($_GET['end_date'])) : '' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <?php if (in_array('Reports Create', $PERMISSION_ARRAY)) { ?>
                                                <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                                <!-- <input type="submit" name="export" value="Export" class="btn btn-info" style="background-color: #39B54A !important;"> -->
                                                <input type="submit" name="generate_pdf" value="Generate PDF" class="btn btn-info" style="background-color: #39B54A !important;">
                                                <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <?php } ?>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-3">
                                        <img src="../assets/images/background/doable_logo.png" style="margin-top:auto; margin-bottom:auto;  height: 60px; width: auto;">
                                    </div>
                                    <div class="col-6">
                                        <?php
                                        $name = $db->Execute("SELECT CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME, CREATED_ON FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
                                        $originalDate = $name->fields['CREATED_ON'];
                                        $newDate = date("m/d/Y H:i:s", strtotime($originalDate));
                                        ?>
                                        <!-- <h3 class="card-title" style="text-align: center; font-weight: bold"><?= $name->fields['NAME'] ?> </h3> -->
                                        <h3 class="card-title" style="text-align: center; font-weight: bold"><?= $title ?></h3>
                                        <h6 class="card-title" style="text-align: center; font-weight: bold">(<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</h5>
                                    </div>
                                    <!-- <div class="btn col-3" style="margin-top:20px">
                                        <button id="export-to-pdf" class="btn btn-info" onclick="viewSamplePdf()">Export
                                            to PDF</button>
                                    </div> -->
                                </div>

                                <div class="table-responsive">
                                    <table id="myTable" class="table table-bordered" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                <?php
                                                $i = 1;
                                                $row = $db_account->Execute("SELECT DISTINCT PK_ENROLLMENT_MASTER FROM DOA_APPOINTMENT_MASTER WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ENROLLMENT_MASTER != 0 AND DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' ORDER BY DATE ASC");
                                                $sum_of_amount_ahead = 0;
                                                while (!$row->EOF) {
                                                    $appointment = $db_account->Execute("SELECT DATE FROM DOA_APPOINTMENT_MASTER WHERE DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' AND PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                    $customer = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                    $enrollment = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                    $used_session_count = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT, PK_SERVICE_MASTER FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                    $PK_SERVICE_MASTER = ($used_session_count->RecordCount() > 0) ? $used_session_count->fields['PK_SERVICE_MASTER'] : 0;
                                                    $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = " . $row->fields['PK_ENROLLMENT_MASTER'] . " AND `PK_SERVICE_MASTER` = " . $PK_SERVICE_MASTER);
                                                    if ($total_session->RecordCount() <= 0 || $total_session->fields['TOTAL_SESSION_COUNT'] == '') {
                                                        $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                    }
                                                    $total_session_count = ($total_session->RecordCount() > 0) ? $total_session->fields['TOTAL_SESSION_COUNT'] : 0;
                                                    $total_bill_and_paid = $db_account->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID, SUM(BALANCE) AS BALANCE FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=" . $row->fields['PK_ENROLLMENT_MASTER']);
                                                    $total_amount = $db_account->Execute("SELECT SUM(TOTAL_AMOUNT) AS TOTAL_AMOUNT FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER`=" . $row->fields['PK_ENROLLMENT_MASTER']);
                                                    $price_per_session = ($total_session_count > 0) ? $total_amount->fields['TOTAL_AMOUNT'] / $total_session_count : 0.00;
                                                    $total_paid = $total_bill_and_paid->fields['TOTAL_PAID'];
                                                    $total_used = $used_session_count->fields['USED_SESSION_COUNT'] * $price_per_session;
                                                    $paid_ahead = $total_amount->fields['TOTAL_AMOUNT'] - $total_used;
                                                    if ($paid_ahead > 0) {
                                                        $sum_of_amount_ahead += $paid_ahead;
                                                    }
                                                    $row->MoveNext();
                                                    $i++;
                                                }
                                                ?>
                                                <th style="width:30%; text-align: center; vertical-align:auto; font-weight: bold">Client</th>
                                                <th style="width:15%; text-align: center; font-weight: bold">Enrollment Id</th>
                                                <th style="width:15%; text-align: center; font-weight: bold">Amount Ahead (<?= number_format($sum_of_amount_ahead, 2) ?>)</th>
                                                <th style="width:15%; text-align: center; font-weight: bold">Date of Last Service</th>
                                                <th style="width:15%; text-align: center; font-weight: bold">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;
                                            $row = $db_account->Execute("SELECT DISTINCT PK_ENROLLMENT_MASTER FROM DOA_APPOINTMENT_MASTER WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ENROLLMENT_MASTER != 0 AND DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' ORDER BY DATE ASC");
                                            while (!$row->EOF) {
                                                $appointment = $db_account->Execute("SELECT DATE FROM DOA_APPOINTMENT_MASTER WHERE DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' AND PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                $customer = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                $enrollment = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                $used_session_count = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT, PK_SERVICE_MASTER FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                $PK_SERVICE_MASTER = ($used_session_count->RecordCount() > 0) ? $used_session_count->fields['PK_SERVICE_MASTER'] : 0;
                                                $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = " . $row->fields['PK_ENROLLMENT_MASTER'] . " AND `PK_SERVICE_MASTER` = " . $PK_SERVICE_MASTER);
                                                if ($total_session->RecordCount() <= 0 || $total_session->fields['TOTAL_SESSION_COUNT'] == '') {
                                                    $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                }
                                                $total_session_count = ($total_session->RecordCount() > 0) ? $total_session->fields['TOTAL_SESSION_COUNT'] : 0;
                                                $total_bill_and_paid = $db_account->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID, SUM(BALANCE) AS BALANCE FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=" . $row->fields['PK_ENROLLMENT_MASTER']);
                                                $total_amount = $db_account->Execute("SELECT SUM(TOTAL_AMOUNT) AS TOTAL_AMOUNT FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER`=" . $row->fields['PK_ENROLLMENT_MASTER']);
                                                $price_per_session = ($total_session_count > 0) ? $total_amount->fields['TOTAL_AMOUNT'] / $total_session_count : 0.00;
                                                $total_paid = $total_bill_and_paid->fields['TOTAL_PAID'];
                                                $total_used = $used_session_count->fields['USED_SESSION_COUNT'] * $price_per_session;
                                                $paid_ahead = $total_amount->fields['TOTAL_AMOUNT'] - $total_used;
                                                if ($paid_ahead > 0) {
                                                    $sum_of_amount_ahead += $paid_ahead;
                                            ?>
                                                    <tr>
                                                        <td><?= $customer->fields['NAME'] ?></td>
                                                        <td style="text-align: center"><?= $enrollment->fields['ENROLLMENT_ID'] ?></td>
                                                        <td style="text-align: right"><?= number_format($paid_ahead, 2) ?></td>
                                                        <td style="text-align: center"><?= date('m-d-Y', strtotime($appointment->fields['DATE'])) ?></td>
                                                        <td style="text-align: right"><?= number_format($total_amount->fields['TOTAL_AMOUNT'], 2) ?></td>
                                                    </tr>
                                            <?php }
                                                $row->MoveNext();
                                                $i++;
                                            } ?>
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
        // $(function () {
        //     $('#myTable').DataTable({
        //         "columnDefs": [
        //             { "targets": [0,2,5], "searchable": false }
        //         ]
        //     });
        // });
        function ConfirmDelete(anchor) {
            let conf = confirm("Are you sure you want to delete?");
            if (conf)
                window.location = anchor.attr("href");
        }
        // function editpage(id, master_id){
        //     window.location.href = "customer.php?id="+id+"&master_id="+master_id;
        //
        // }

        // function viewSamplePdf() {
        //     let DOCUMENT_TEMPLATE = "asldjal"//$('#myTable').html();
        //     $.ajax({
        //         url: "ajax/AjaxFunctions.php",
        //         type: 'POST',
        //         data: {FUNCTION_NAME: 'viewSamplePdf', DOCUMENT_TEMPLATE: DOCUMENT_TEMPLATE},
        //         success:function (data) {
        //             console.log(data);
        //             window.open(
        //                 data,
        //                 '_blank' // <- This is what makes it open in a new window.
        //             );
        //         },
        //         error: (error) => {
        //             console.log(JSON.stringify(error));
        //         }
        //     });
        // }

        var buttonElement = document.querySelector("#myTable");
        buttonElement.addEventListener('click', function() {
            var pdfContent = document.getElementById("pdf-content").innerHTML;
            var windowObject = window.open();

            windowObject.document.write(pdfContent);

            windowObject.print();
            windowObject.close();
        });
    </script>

</body>

</html>

<script>
    $(".week-picker").datepicker({
        showWeek: true,
        showOtherMonths: true,
        selectOtherMonths: true,
        changeMonth: true,
        changeYear: true,
        calculateWeek: wk,
        beforeShowDay: function(date) {
            if (date.getDay() === 0) {
                return [true, ''];
            }
            return [false, ''];
        },
        onSelect: function(dateText, inst) {
            let d = new Date(dateText);
            let start_date = (d.getMonth() + 1) + '/' + d.getDate() + '/' + d.getFullYear();
            $(this).closest('form').find('#start_date').val(start_date);
            d.setDate(d.getDate() - 363);
            let week_number = $.datepicker.iso8601Week(d);
            let report_type = $(this).closest('form').find('#NAME').val();
            $(this).val("Week Number " + week_number);
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {
                    FUNCTION_NAME: 'getReportDetails',
                    REPORT_TYPE: report_type,
                    WEEK_NUMBER: week_number,
                    YEAR: (d.getFullYear() + 1)
                },
                async: false,
                cache: false,
                success: function(result) {
                    $('#last_export_message').text(result);
                }
            });
        }
    });

    $(document).ready(function() {
        $("#START_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(dateText, inst) {
                let d = new Date(dateText);
                let start_date = (d.getMonth() + 1) + '/' + d.getDate() + '/' + d.getFullYear();
                $(this).closest('form').find('#start_date').val(start_date);
                $("#END_DATE").datepicker("option", "minDate", dateText);
                $("#START_DATE, #END_DATE").trigger("change");
            }
        });
        $("#END_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(dateText, inst) {
                let d = new Date(dateText);
                let end_date = (d.getMonth() + 1) + '/' + d.getDate() + '/' + d.getFullYear();
                $(this).closest('form').find('#end_date').val(end_date);
                $("#START_DATE").datepicker("option", "maxDate", dateText)
            }
        });
    });

    function wk(d) {
        var d = new Date(d);
        d.setDate(d.getDate() - 363);
        return '#' + $.datepicker.iso8601Week(d);
    }

    function showReportLog(param) {
        let report_type = $(param).closest('form').find('#NAME').val();
        $.ajax({
            url: "includes/get_report_details.php",
            type: "POST",
            data: {
                REPORT_TYPE: report_type
            },
            async: false,
            cache: false,
            success: function(result) {
                $(param).closest('form').find('#export_log').html(result);
            }
        });
    }
</script>