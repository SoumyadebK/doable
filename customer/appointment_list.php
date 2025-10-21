<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
$LOCATION_ARRAY = explode(',', $_SESSION['DEFAULT_LOCATION_ID']);
$PK_USER_MASTER = $_SESSION['PK_USER_MASTER'];


$status_check = empty($_GET['status']) ? '' : $_GET['status'];
$appointment_time = ' ';
$order_by = " ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC";
if ($status_check == 'previous') {
    $appointment_time = " AND DOA_APPOINTMENT_MASTER.DATE < '" . date('Y-m-d') . "'";
} elseif ($status_check == 'future') {
    $appointment_time = " AND DOA_APPOINTMENT_MASTER.DATE > '" . date('Y-m-d') . "'";
} elseif (empty($_GET['START_DATE']) && empty($_GET['END_DATE']) && empty($_GET['search_text'])) {
    $appointment_time = " AND DOA_APPOINTMENT_MASTER.DATE = '" . date('Y-m-d') . "'";
} elseif (empty($_GET['START_DATE']) && empty($_GET['END_DATE']) && !empty($_GET['search_text'])) {
    $appointment_time = " AND DOA_APPOINTMENT_MASTER.DATE >= '" . date('Y-m-d') . "'";
    $order_by = " ORDER BY DOA_APPOINTMENT_MASTER.DATE ASC, DOA_APPOINTMENT_MASTER.START_TIME ASC";
}

$appointment_status = empty($_GET['appointment_status']) ? '1, 2, 3, 5, 7, 8' : $_GET['appointment_status'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 4) {
    header("location:../login.php");
    exit;
}

$START_DATE = ' ';
$END_DATE = ' ';
if (!empty($_GET['START_DATE'])) {
    $START_DATE = " AND DOA_APPOINTMENT_MASTER.DATE >= '" . date('Y-m-d', strtotime($_GET['START_DATE'])) . "'";
}
if (!empty($_GET['END_DATE'])) {
    $END_DATE = " AND DOA_APPOINTMENT_MASTER.DATE <= '" . date('Y-m-d', strtotime($_GET['END_DATE'])) . "'";
}

$search_text = '';
$search = $START_DATE . $END_DATE . ' ';
if (!empty($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = $START_DATE . $END_DATE . " AND (DOA_ENROLLMENT_MASTER.ENROLLMENT_ID LIKE '%" . $search_text . "%' OR CUSTOMER.FIRST_NAME LIKE '%" . $search_text . "%' OR SERVICE_PROVIDER.FIRST_NAME LIKE '%" . $search_text . "%' OR CUSTOMER.LAST_NAME LIKE '%" . $search_text . "%' OR SERVICE_PROVIDER.LAST_NAME LIKE '%" . $search_text . "%' OR CUSTOMER.EMAIL_ID LIKE '%" . $search_text . "%' OR CUSTOMER.PHONE LIKE '%" . $search_text . "%')";
}

$standing = 0;
$standing_select = ' ';
$standing_cond = ' ';
$standing_group = ' GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER ';
if (isset($_GET['standing'])) {
    if ($_GET['standing'] == 1) {
        $standing = 1;
        $standing_select = ' MIN(DOA_APPOINTMENT_MASTER.DATE) AS BEGINNING_DATE, MAX(DOA_APPOINTMENT_MASTER.DATE) AS END_DATE, ';
        $standing_cond = ' AND DOA_APPOINTMENT_MASTER.STANDING_ID > 0 ';
        $standing_group = " GROUP BY DOA_APPOINTMENT_MASTER.STANDING_ID ";
    } else {
        $standing_cond = ' AND DOA_APPOINTMENT_MASTER.STANDING_ID = 0 ';
    }
}

if ($standing == 1) {
    $title = "All Standing Appointment";
    $appointment_time = ' ';
} else {
    $title = "Today's Appointment";
}

$ALL_APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
                            $standing_select
                            DOA_APPOINTMENT_MASTER.STANDING_ID,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
                            DOA_APPOINTMENT_MASTER.GROUP_NAME,
                            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
                            DOA_APPOINTMENT_MASTER.DATE,
                            DOA_APPOINTMENT_MASTER.START_TIME,
                            DOA_APPOINTMENT_MASTER.END_TIME,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_MASTER.IS_CHARGED,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            GROUP_CONCAT(DISTINCT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME)) SEPARATOR ', ') AS SERVICE_PROVIDER_NAME,
                            GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ', ') AS CUSTOMER_NAME
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                        
                        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                                
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        WHERE (CUSTOMER.IS_DELETED = 0 OR CUSTOMER.IS_DELETED IS null) 
                        AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
                        AND DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN ($appointment_status)
                        AND DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = '$PK_USER_MASTER'
                        $standing_cond
                        $appointment_time
                        $search
                        $standing_group
                        $order_by";

$query = $db_account->Execute($ALL_APPOINTMENT_QUERY);

$number_of_result =  $query->RecordCount();
$number_of_page = ceil($number_of_result / $results_per_page);

if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page - 1) * $results_per_page;

?>


<!DOCTYPE html>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
<style>
    table th {
        font-weight: bold;
    }

    .sortable.asc::after {
        content: " ▲";
    }

    .sortable.desc::after {
        content: " ▼";
    }
</style>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <div class="container-fluid body_content">
                <div class="row">
                    <div id="add_buttons" class="d-flex justify-content-center align-items-center" style="position: fixed; bottom: 0">
                        <!--<button type="button" id="group_class" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=group_class'"><i class="fa fa-plus-circle"></i> Group Class</button>
                    <button type="button" id="int_app" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=int_app'"><i class="fa fa-plus-circle"></i> INT APP</button>
                    <button type="button" id="appointment" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=appointment'"><i class="fa fa-plus-circle"></i> Appointment</button>
                    <button type="button" id="standing" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=standing'"><i class="fa fa-plus-circle"></i> Standing</button>
                    <button type="button" id="ad_hoc" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=ad_hoc'"><i class="fa fa-plus-circle"></i> Ad-hoc Appointment</button>-->
                        <button type="button" id="appointments" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="showMessage()"><i class="fa fa-plus-circle"></i> Appointments</button>
                        <button type="button" id="operations" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='operations.php'"><i class="ti-layers-alt"></i> <?= $operation_tab_title ?></button>
                    </div>
                </div>

                <form class="form-material form-horizontal" id="search_form" action="" method="get">
                    <div class="row page-titles">
                        <div class="col-md-2 align-self-center">
                            <?php if ($status_check == 'previous') { ?>
                                <h4 class="text-themecolor">Previous Appointments</h4>
                            <?php } elseif ($status_check == 'future') { ?>
                                <h4 class="text-themecolor">Future Appointments</h4>
                            <?php } else { ?>
                                <h4 class="text-themecolor"><?= $title ?></h4>
                            <?php } ?>
                        </div>

                        <?php if (empty($_GET['status']) || $status_check == 'future') { ?>
                            <div class="col-md-2 align-self-center">
                                <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='appointment_list.php?status=previous'">Previous Appointments</button>
                            </div>
                        <?php } elseif ($status_check == 'previous') { ?>
                            <div class="col-md-2 align-self-center">
                                <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='appointment_list.php?status=future'">Future Appointments</button>
                            </div>
                        <?php } ?>

                        <div class="col-md-1 align-self-center">
                            <?php if ($standing == 0) { ?>
                                <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='appointment_list.php?standing=1'">Show Standing</button>
                            <?php } else { ?>
                                <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='appointment_list.php'">Show Normal</button>
                            <?php } ?>
                        </div>

                        <div class="col-2">
                            <div class="form-material form-horizontal">
                                <select class="form-control" name="appointment_status" id="appointment_status" onchange="$('#search_form').submit()">
                                    <option value="">Select Status</option>
                                    <?php
                                    $row = $db->Execute("SELECT * FROM DOA_APPOINTMENT_STATUS WHERE ACTIVE = 1");
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS']; ?>" <?= ($row->fields['PK_APPOINTMENT_STATUS'] == $appointment_status) ? "selected" : "" ?>><?= $row->fields['APPOINTMENT_STATUS'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-5">
                            <div class="input-group">
                                <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" placeholder="Start Date" value="<?= !empty($_GET['START_DATE']) ? $_GET['START_DATE'] : '' ?>">&nbsp;&nbsp;&nbsp;&nbsp;
                                <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" placeholder="End Date" value="<?= !empty($_GET['END_DATE']) ? $_GET['END_DATE'] : '' ?>">&nbsp;&nbsp;&nbsp;&nbsp;
                                <input class="form-control" type="text" id="search_text" name="search_text" placeholder="Search.." value="<?= $search_text ?>">
                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white input-group-btn m-b-1" style="margin-bottom: 1px"><i class="fa fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="row">
                    <div id="appointments" class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table <?= ($standing == 0) ? 'table-striped' : '' ?> border" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                <th data-type="number" class="sortable" style="cursor: pointer">No</th>
                                                <th data-type="number" class="sortable" style="cursor: pointer">Service Name</th>
                                                <th data-type="number" class="sortable" style="cursor: pointer">Class Name</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Customer</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Enrollment ID</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer"><?= $service_provider_title ?></th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Day</th>
                                                <th data-date data-order class="sortable" style="cursor: pointer">Date</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Time</th>
                                                <th>Paid</th>
                                                <th style="width: 8%;">Status</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                            $i = $page_first_result + 1;
                                            $appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY, $page_first_result . ',' . $results_per_page);
                                            while (!$appointment_data->EOF) {
                                                if ($standing == 0) { ?>
                                                    <tr>
                                                    <?php } else { ?>
                                                    <tr class="header" onclick="showStandingAppointmentDetails(this, <?= $appointment_data->fields['STANDING_ID'] ?>, <?= $appointment_data->fields['PK_APPOINTMENT_MASTER'] ?>)" style="cursor: pointer;">
                                                    <?php } ?>
                                                    <td><?= $i; ?></td>
                                                    <td><?= (($appointment_data->fields['APPOINTMENT_TYPE'] == 'NORMAL') ? 'Private Session' : (($appointment_data->fields['APPOINTMENT_TYPE'] == 'AD-HOC') ? 'Ad-Hoc' : 'Group Class')) ?>
                                                        <?php if ($appointment_data->fields['STANDING_ID'] > 0) { ?>
                                                            <span style="font-weight: bold; color: #1B72B8">(S)</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td><?= $appointment_data->fields['GROUP_NAME'] ?></td>
                                                    <td><?= $appointment_data->fields['CUSTOMER_NAME'] ?></td>
                                                    <?php if (!empty($appointment_data->fields['ENROLLMENT_ID']) || !empty($appointment_data->fields['ENROLLMENT_NAME'])) { ?>
                                                        <td><?= (($appointment_data->fields['ENROLLMENT_NAME']) ? $appointment_data->fields['ENROLLMENT_NAME'] . ' - ' : '') . $appointment_data->fields['ENROLLMENT_ID'] . " || " . $appointment_data->fields['SERVICE_NAME'] . " || " . $appointment_data->fields['SERVICE_CODE'] ?></td>
                                                    <?php } elseif (empty($appointment_data->fields['SERVICE_NAME']) && empty($appointment_data->fields['SERVICE_CODE'])) { ?>
                                                        <td><?= $appointment_data->fields['SERVICE_NAME'] . "  " . $appointment_data->fields['SERVICE_CODE'] ?></td>
                                                    <?php } else { ?>
                                                        <td><?= $appointment_data->fields['SERVICE_NAME'] . " || " . $appointment_data->fields['SERVICE_CODE'] ?></td>
                                                    <?php } ?>
                                                    <td><?= $appointment_data->fields['SERVICE_PROVIDER_NAME'] ?></td>
                                                    <td><?= date('l', strtotime($appointment_data->fields['DATE'])) ?></td>

                                                    <?php if ($standing == 0) { ?>
                                                        <td><?= date('m/d/Y', strtotime($appointment_data->fields['DATE'])) ?></td>
                                                    <?php } else { ?>
                                                        <td><?= date('m/d/Y', strtotime($appointment_data->fields['BEGINNING_DATE'])) ?> - <?= date('m/d/Y', strtotime($appointment_data->fields['END_DATE'])) ?></td>&nbsp;&nbsp;&nbsp;
                                                    <?php } ?>

                                                    <td><?= date('h:i A', strtotime($appointment_data->fields['START_TIME'])) . " - " . date('h:i A', strtotime($appointment_data->fields['END_TIME'])) ?></td>
                                                    <td><?= ($appointment_data->fields['IS_PAID'] == 1) ? 'Paid' : 'Unpaid' ?></td>
                                                    <td style="text-align: left; color: <?= $appointment_data->fields['APPOINTMENT_COLOR'] ?>">
                                                        <?= $appointment_data->fields['APPOINTMENT_STATUS'] ?>&nbsp;
                                                        <?php if ($appointment_data->fields['IS_CHARGED'] == 1) { ?>
                                                            <i class="ti-money"></i>
                                                        <?php } ?>
                                                    </td>
                                                    </tr>
                                                <?php $appointment_data->MoveNext();
                                                $i++;
                                            } ?>
                                        </tbody>
                                    </table>

                                    <div class="center">
                                        <div class="pagination outer">
                                            <ul>
                                                <?php if ($page > 1) { ?>
                                                    <li><a href="appointment_list.php?status=<?= $status_check ?>&appointment_status=<?= $appointment_status ?>&page=1">&laquo;</a></li>
                                                    <li><a href="appointment_list.php?status=<?= $status_check ?>&appointment_status=<?= $appointment_status ?>&page=<?= ($page - 1) ?>">&lsaquo;</a></li>
                                                <?php }
                                                for ($page_count = 1; $page_count <= $number_of_page; $page_count++) {
                                                    if ($page_count == $page || $page_count == ($page + 1) || $page_count == ($page - 1) || $page_count == $number_of_page) {
                                                        echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="appointment_list.php?status=' . $status_check . '&appointment_status=' . $appointment_status . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                    } elseif ($page_count == ($number_of_page - 1)) {
                                                        echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                    } else {
                                                        echo '<li><a class="hidden" href="appointment_list.php?status=' . $status_check . 'appointment_status=' . $appointment_status . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                    }
                                                }
                                                if ($page < $number_of_page) { ?>
                                                    <li><a href="appointment_list.php?status=<?= $status_check ?>&appointment_status=<?= $appointment_status ?>&page=<?= ($page + 1) ?>">&rsaquo;</a></li>
                                                    <li><a href="appointment_list.php?status=<?= $status_check ?>&appointment_status=<?= $appointment_status ?>&page=<?= $number_of_page ?>">&raquo;</a></li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>

    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <script>
        $(function() {
            startDate = $("#START_DATE").datepicker({
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
        });

        $(document).ready(function() {
            $(".sortable").on("click", function() {
                var table = $(this).closest("table");
                var tbody = table.find("tbody");
                var rows = tbody.find("tr").toArray();
                var index = $(this).index();
                var asc = !$(this).hasClass("asc");
                var isDate = $(this).is("[data-date]");
                var type = $(this).data("type");

                // Remove old sorting indicators
                table.find(".sortable").removeClass("asc desc");
                $(this).addClass(asc ? "asc" : "desc");

                rows.sort(function(a, b) {
                    var A = $(a).children("td").eq(index).text().trim();
                    var B = $(b).children("td").eq(index).text().trim();

                    // Handle data type
                    if (isDate) {
                        A = new Date(A);
                        B = new Date(B);
                    } else if (type === "number") {
                        A = parseFloat(A.replace(/[^0-9.\-]/g, "")) || 0;
                        B = parseFloat(B.replace(/[^0-9.\-]/g, "")) || 0;
                    } else {
                        A = A.toLowerCase();
                        B = B.toLowerCase();
                    }

                    if (A < B) return asc ? -1 : 1;
                    if (A > B) return asc ? 1 : -1;
                    return 0;
                });

                // Append sorted rows
                $.each(rows, function(i, row) {
                    tbody.append(row);
                });
            });
        });

        function showMessage() {
            if (<?= count($LOCATION_ARRAY) ?> === 1) {
                window.location.href = 'create_appointment.php';
            } else {
                swal("Select One Location!", "Only one location can be selected on top of the page in order to schedule an appointment.", "error");
            }
        }
    </script>

    <script>
        function showStandingAppointmentDetails(param, STANDING_ID, PK_APPOINTMENT_MASTER) {
            let $nextRows = $(param).nextUntil('tr.header');

            if ($nextRows.length) {
                // If details are already shown, remove them
                $nextRows.remove();
            } else {
                // Otherwise, fetch and show details
                $.ajax({
                    url: "pagination/get_standing_appointment.php",
                    type: 'GET',
                    data: {
                        STANDING_ID: STANDING_ID,
                        PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                    },
                    success: function(result) {
                        $(result).insertAfter($(param).closest('tr'));
                    }
                });
            }
        }

        function ConfirmDelete(PK_APPOINTMENT_MASTER, type) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
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
                            type: type,
                            PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                        },
                        success: function(data) {
                            let currentURL = window.location.href;
                            let extractedPart = currentURL.substring(currentURL.lastIndexOf("/") + 1);
                            console.log(extractedPart);
                            window.location.href = extractedPart;
                        }
                    });
                }
            });
        }

        function selectStatus(param) {
            var status = $(param).val();
            window.location.href = "appointment_list.php?appointment_status=" + status;

        }
    </script>