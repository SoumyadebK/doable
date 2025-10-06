<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$date_start = '';
$date_end = '';

$START_DATE = ' ';
$END_DATE = ' ';

if (empty($_GET['START_DATE']) && empty($_GET['END_DATE']) && empty($_GET['search_text'])) {
    $START_DATE = " AND DOA_SPECIAL_APPOINTMENT.DATE = '" . date('Y-m-d') . "'";
}

$appointment_status = empty($_GET['appointment_status']) ? '1, 2, 3, 5, 7, 8' : $_GET['appointment_status'];

if (!empty($_GET['START_DATE'])) {
    $date_start = $_GET['START_DATE'];
    $START_DATE = " AND DOA_SPECIAL_APPOINTMENT.DATE >= '" . date('Y-m-d', strtotime($_GET['START_DATE'])) . "'";
}
if (!empty($_GET['END_DATE'])) {
    $date_end = $_GET['END_DATE'];
    $END_DATE = " AND DOA_SPECIAL_APPOINTMENT.DATE <= '" . date('Y-m-d', strtotime($_GET['END_DATE'])) . "'";
}

$search_text = '';
$search = $START_DATE . $END_DATE . ' ';
if (!empty($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = $START_DATE . $END_DATE . " AND (DOA_SPECIAL_APPOINTMENT.TITLE LIKE '%" . $search_text . "%') ";
}

$standing = 0;
$standing_cond = ' ';
$standing_group = ' GROUP BY DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT ';
if (isset($_GET['standing'])) {
    if ($_GET['standing'] == 1) {
        $standing = 1;
        $standing_cond = ' AND DOA_SPECIAL_APPOINTMENT.STANDING_ID > 0 ';
        $standing_group = " GROUP BY DOA_SPECIAL_APPOINTMENT.STANDING_ID ";
    } else {
        $standing_cond = ' AND DOA_SPECIAL_APPOINTMENT.STANDING_ID = 0 ';
    }
}

if ($standing == 1) {
    $title = "All Standing To-Do";
} else {
    $title = "All To-Do";
}

$SPECIAL_APPOINTMENT_QUERY = "SELECT
                                    DOA_SPECIAL_APPOINTMENT.*,
                                    DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                                    DOA_APPOINTMENT_STATUS.STATUS_CODE,
                                    DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                                    DOA_SCHEDULING_CODE.COLOR_CODE,
                                    DOA_SCHEDULING_CODE.DURATION,
                                    GROUP_CONCAT(DISTINCT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME)) SEPARATOR ', ') AS SERVICE_PROVIDER_NAME,
                                    GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ', ') AS CUSTOMER_NAME
                                FROM
                                    `DOA_SPECIAL_APPOINTMENT`
                                LEFT JOIN DOA_SPECIAL_APPOINTMENT_USER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT
                                LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_SPECIAL_APPOINTMENT_USER.PK_USER = SERVICE_PROVIDER.PK_USER
                                        
                                LEFT JOIN DOA_SPECIAL_APPOINTMENT_CUSTOMER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_SPECIAL_APPOINTMENT
                                LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                                        
                                LEFT JOIN DOA_MASTER.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS
                                LEFT JOIN DOA_SCHEDULING_CODE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE = DOA_SPECIAL_APPOINTMENT.PK_SCHEDULING_CODE
                                WHERE DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN ($appointment_status)
                                AND DOA_SPECIAL_APPOINTMENT.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
                                " . $standing_cond . $search . $standing_group . "
                                ORDER BY DOA_SPECIAL_APPOINTMENT.DATE ASC, DOA_SPECIAL_APPOINTMENT.START_TIME ASC";

$query = $db_account->Execute($SPECIAL_APPOINTMENT_QUERY);

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

                <form class="form-material form-horizontal" id="search_form" action="" method="get">
                    <input type="hidden" name="standing" id="standing" value="<?= $standing ?>">
                    <div class="row page-titles">
                        <div class="col-md-2 align-self-center">
                            <h4 class="text-themecolor"><?= $title ?></h4>
                        </div>

                        <div class="col-md-2 align-self-center">
                            <?php if ($standing == 0) { ?>
                                <button type="submit" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="$('#standing').val(1)">Show Standing</button>
                            <?php } else { ?>
                                <button type="submit" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="$('#standing').val(0);">Show Normal</button>
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

                        <div class="col-6">
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
                                    <table id="to_do_list" class="table <?= ($standing == 0) ? 'table-striped' : '' ?> border" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                <th data-type="number" class="sortable" style="cursor: pointer">No</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Title</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Service Provider</th>
                                                <!--<th data-type="string" class="sortable" style="cursor: pointer">Customer</th>-->
                                                <th data-type="string" class="sortable" style="cursor: pointer">Day</th>
                                                <th data-date data-order class="sortable" style="cursor: pointer">Date</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Time</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                            $i = $page_first_result + 1;
                                            $special_appointment_data = $db_account->Execute($SPECIAL_APPOINTMENT_QUERY, $page_first_result . ',' . $results_per_page);
                                            while (!$special_appointment_data->EOF) {
                                                if ($standing == 1 && $special_appointment_data->fields['STANDING_ID'] > 0) {
                                                    $standing_date = $db_account->Execute("SELECT MIN(DOA_SPECIAL_APPOINTMENT.DATE) AS BEGINNING_DATE, MAX(DOA_SPECIAL_APPOINTMENT.DATE) AS END_DATE FROM `DOA_SPECIAL_APPOINTMENT` WHERE STANDING_ID = " . $special_appointment_data->fields['STANDING_ID']);
                                                }
                                            ?>

                                                <?php
                                                if ($standing == 0) { ?>
                                                    <tr>
                                                    <?php } else { ?>
                                                    <tr onclick="showStandingToDoDetails(this, <?= $special_appointment_data->fields['STANDING_ID'] ?>)" style="cursor: pointer;">
                                                    <?php } ?>
                                                    <td><?= $i; ?></td>
                                                    <td>
                                                        <?= $special_appointment_data->fields['TITLE'] ?>
                                                        <?php if ($special_appointment_data->fields['STANDING_ID'] > 0) { ?>
                                                            <span style="font-weight: bold; color: #1B72B8">(S)</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td><?= $special_appointment_data->fields['SERVICE_PROVIDER_NAME'] ?></td>
                                                    <!--<td><?php /*=$special_appointment_data->fields['CUSTOMER_NAME']*/ ?></td>-->
                                                    <td><?= date('l', strtotime($special_appointment_data->fields['DATE'])) ?></td>
                                                    <?php if ($standing == 0) { ?>
                                                        <td><?= date('m/d/Y', strtotime($special_appointment_data->fields['DATE'])) ?></td>&nbsp;&nbsp;&nbsp;
                                                    <?php } else { ?>
                                                        <td><?= date('m/d/Y', strtotime($standing_date->fields['BEGINNING_DATE'])) ?> - <?= date('m/d/Y', strtotime($standing_date->fields['END_DATE'])) ?></td>&nbsp;&nbsp;&nbsp;
                                                    <?php } ?>

                                                    <td><?= date('h:i A', strtotime($special_appointment_data->fields['START_TIME'])) . " - " . date('h:i A', strtotime($special_appointment_data->fields['END_TIME'])) ?></td>
                                                    <?php if ($standing == 0) { ?>
                                                        <td style="color: <?= $special_appointment_data->fields['APPOINTMENT_COLOR'] ?>"><?= $special_appointment_data->fields['APPOINTMENT_STATUS'] ?></td>
                                                    <?php } else { ?>
                                                        <td></td>
                                                    <?php } ?>
                                                    <td>
                                                        <?php if ($standing == 0) { ?>
                                                            <?php if (in_array("To-Do Edit", $PERMISSION_ARRAY)) { ?>
                                                                <a href="edit_to_do.php?id=<?= $special_appointment_data->fields['PK_SPECIAL_APPOINTMENT'] ?>" title="Edit" style="font-size:18px"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;
                                                            <?php } ?>
                                                            <?php if (in_array("To-Do Delete", $PERMISSION_ARRAY)) { ?>
                                                                <a href="javascript:" onclick="ConfirmDelete(<?= $special_appointment_data->fields['PK_SPECIAL_APPOINTMENT'] ?>);" title="Delete" style="font-size:18px"><i class="fa fa-trash"></i></a>&nbsp;&nbsp;&nbsp;
                                                            <?php } ?>
                                                        <?php } else { ?>
                                                            <?php if (in_array("To-Do Edit", $PERMISSION_ARRAY)) { ?>
                                                                <a href="edit_to_do.php?id=<?= $special_appointment_data->fields['PK_SPECIAL_APPOINTMENT'] ?>&standing=1" title="Edit" style="font-size:18px"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;
                                                            <?php } ?>
                                                            <?php if (in_array("To-Do Delete", $PERMISSION_ARRAY)) { ?>
                                                                <a href="javascript:" onclick="ConfirmDeleteStanding(<?= $special_appointment_data->fields['STANDING_ID'] ?>);" title="Delete All Standing" style="font-size:18px"><i class="fa fa-trash-alt"></i></a>&nbsp;&nbsp;&nbsp;
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </td>
                                                    </tr>

                                                    <!--<tbody class="standing_list" style="display: none; background-color: #dee2e6;">

                                    </tbody>-->
                                                <?php $special_appointment_data->MoveNext();
                                                $i++;
                                            } ?>
                                        </tbody>
                                    </table>

                                    <div class="center">
                                        <div class="pagination outer">
                                            <ul>
                                                <?php if ($page > 1) { ?>
                                                    <li><a href="to_do_list.php?START_DATE=<?= $date_start ?>&END_DATE=<?= $date_end ?>&appointment_status=<?= $appointment_status ?>&page=1">&laquo;</a></li>
                                                    <li><a href="to_do_list.php?START_DATE=<?= $date_start ?>&END_DATE=<?= $date_end ?>&appointment_status=<?= $appointment_status ?>&page=<?= ($page - 1) ?>">&lsaquo;</a></li>
                                                <?php }
                                                for ($page_count = 1; $page_count <= $number_of_page; $page_count++) {
                                                    if ($page_count == $page || $page_count == ($page + 1) || $page_count == ($page - 1) || $page_count == $number_of_page) {
                                                        echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="to_do_list.php?START_DATE=' . $date_start . '&END_DATE=' . $date_end . '&appointment_status=' . $appointment_status . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                    } elseif ($page_count == ($number_of_page - 1)) {
                                                        echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                    } else {
                                                        echo '<li><a class="hidden" href="to_do_list.php?START_DATE=' . $date_start . '&END_DATE=' . $date_end . '&appointment_status=' . $appointment_status . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                    }
                                                }
                                                if ($page < $number_of_page) { ?>
                                                    <li><a href="to_do_list.php?START_DATE=<?= $date_start ?>&END_DATE=<?= $date_end ?>&appointment_status=<?= $appointment_status ?>&page=<?= ($page + 1) ?>">&rsaquo;</a></li>
                                                    <li><a href="to_do_list.php?START_DATE=<?= $date_start ?>&END_DATE=<?= $date_end ?>&appointment_status=<?= $appointment_status ?>&page=<?= $number_of_page ?>">&raquo;</a></li>
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
    </script>

    <script>
        function showStandingToDoDetails(param, STANDING_ID) {
            $(param).nextUntil('tr.header').remove();
            $.ajax({
                url: "pagination/get_standing_to_do.php",
                type: 'GET',
                data: {
                    STANDING_ID: STANDING_ID
                },
                success: function(result) {
                    $(result).insertAfter($(param).closest('tr'));
                }
            });
        }

        function ConfirmDelete(PK_SPECIAL_APPOINTMENT, type) {
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
                            FUNCTION_NAME: 'deleteSpecialAppointment',
                            PK_SPECIAL_APPOINTMENT: PK_SPECIAL_APPOINTMENT,
                            IS_STANDING: 0
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

        function ConfirmDeleteStanding(STANDING_ID) {
            Swal.fire({
                title: "Are you sure?",
                text: "You want to delete all standing appointment?",
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
                            FUNCTION_NAME: 'deleteSpecialAppointment',
                            PK_SPECIAL_APPOINTMENT: STANDING_ID,
                            IS_STANDING: 1
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
            window.location.href = "to_do_list.php?appointment_status=" + status;

        }
    </script>