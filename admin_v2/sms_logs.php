<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$title = "SMS Logs";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
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

            <?php require_once('layout/setup_menu.php') ?>
            <div class="container-fluid body_content m-0" style="margin-top: 0px !important;">
                <div class="row page-titles">
                    <div class="col-md-3 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>

                    <div class="col-md-9 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="myTable" class="table table-striped border" data-page-length="50">
                                        <thead>
                                            <tr>
                                                <th width="10%">Send Date</th>
                                                <th width="10%">Location</th>
                                                <th width="10%">Customer Name</th>
                                                <th width="10%">Phone Number</th>
                                                <th width="30%">Message</th>
                                                <th width="10%">Status</th>
                                                <th width="20%">Error Message</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $row = $db_account->Execute("SELECT DOA_SMS_LOG.*, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_LOCATION.LOCATION_NAME FROM `DOA_SMS_LOG` INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_SMS_LOG.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_SMS_LOG.PK_LOCATION = DOA_LOCATION.PK_LOCATION WHERE DOA_SMS_LOG.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") ORDER BY `TRIGGER_TIME` DESC");
                                            while (!$row->EOF) { ?>
                                                <tr>
                                                    <td><?= date('m/d/Y h:i A', strtotime($row->fields['TRIGGER_TIME'])) ?></td>
                                                    <td><?= $row->fields['LOCATION_NAME'] ?></td>
                                                    <td><?= $row->fields['FIRST_NAME'] . ' ' . $row->fields['LAST_NAME'] ?></td>
                                                    <td><?= $row->fields['PHONE_NUMBER'] ?></td>
                                                    <td><?= $row->fields['MESSAGE'] ?></td>
                                                    <td><?= ($row->fields['IS_ERROR'] == 0) ? 'Success' : 'Failed' ?></td>
                                                    <td style="color: red;"><?= $row->fields['ERROR_MESSAGE'] ?></td>
                                                </tr>
                                            <?php $row->MoveNext();
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
        $(function() {
            // Destroy existing DataTable if exists
            if ($.fn.DataTable.isDataTable('#myTable')) {
                $('#myTable').DataTable().clear().destroy();
                $('#myTable').off(); // remove old events
            }

            // Remove old custom filters to avoid stacking
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                return fn.name !== "dateRangeFilter";
            });

            let table = $('#myTable').DataTable({
                order: [
                    [0, 'desc']
                ],
                columnDefs: [{
                    type: 'date',
                    targets: 0
                }],
                dom: '<"d-flex justify-content-between align-items-center"l<"date-filter">fB>rtip',
                buttons: [{
                    extend: 'excelHtml5',
                    text: 'Export to Excel',
                    title: 'Payment Register',
                    exportOptions: {
                        columns: ':visible'
                    }
                }]
            });

            $("div.date-filter").html(`
                    <div class="input-group">
                        <input type="text" id="START_DATE" class="form-control form-control-sm" placeholder="From Date">
                        <input type="text" id="END_DATE" class="form-control form-control-sm ms-2" placeholder="To Date">
                    </div>
                `);

            // Init datepickers
            $("#START_DATE").datepicker({
                numberOfMonths: 1,
                onSelect: function(selected) {
                    $("#END_DATE").datepicker("option", "minDate", selected);
                    table.draw();
                }
            });

            $("#END_DATE").datepicker({
                numberOfMonths: 1,
                onSelect: function(selected) {
                    $("#START_DATE").datepicker("option", "maxDate", selected);
                    table.draw();
                }
            });

            // Custom filtering function for date range (named for easy removal)
            function dateRangeFilter(settings, data, dataIndex) {
                var min = $('#START_DATE').val();
                var max = $('#END_DATE').val();
                var date = data[0]; // first column

                if (!date) return true;

                var tableDate = new Date(date);

                if ((min === "" && max === "") ||
                    (min === "" && tableDate <= new Date(max)) ||
                    (new Date(min) <= tableDate && max === "") ||
                    (new Date(min) <= tableDate && tableDate <= new Date(max))) {
                    return true;
                }
                return false;
            }
            dateRangeFilter.name = "dateRangeFilter"; // label it
            $.fn.dataTable.ext.search.push(dateRangeFilter);

            // Event listener for inputs
            $('#START_DATE, #END_DATE').on('change keyup', function() {
                table.draw();
            });
        });
    </script>
</body>

</html>