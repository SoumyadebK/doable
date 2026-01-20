<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;
$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$type = !empty($_GET['type']) ? $_GET['type'] : '';
$appointment_type = ' ';
if ($type === 'posted') {
    $appointment_type = " AND (DOA_APPOINTMENT_MASTER.IS_CHARGED = 1 || DOA_APPOINTMENT_ENROLLMENT.IS_CHARGED = 1) ";
} elseif ($type === 'unposted') {
    $appointment_type = " AND (DOA_APPOINTMENT_MASTER.IS_CHARGED = 0 || DOA_APPOINTMENT_ENROLLMENT.IS_CHARGED = 0) ";
} elseif ($type === 'cancelled') {
    $appointment_type = " AND DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN (4, 6)";
} else {
    $appointment_type = " AND DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN (1, 2, 3, 5, 7, 8)";
}

$START_DATE = ' ';
$END_DATE = ' ';
if (isset($_GET['START_DATE']) && $_GET['START_DATE'] != '') {
    $START_DATE = " AND DOA_APPOINTMENT_MASTER.DATE >= '$_GET[START_DATE]'";
}
if (isset($_GET['END_DATE']) && $_GET['END_DATE'] != '') {
    $END_DATE = " AND DOA_APPOINTMENT_MASTER.DATE <= '$_GET[END_DATE]'";
}

$search_text = '';
$search = $START_DATE . $END_DATE . ' ';
if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = $START_DATE . $END_DATE . " AND (DOA_SERVICE_MASTER.SERVICE_NAME LIKE '%" . $search_text . "%' OR DOA_SERVICE_CODE.SERVICE_CODE LIKE '%" . $search_text . "%' OR CUSTOMER.FIRST_NAME LIKE '%" . $search_text . "%' OR SERVICE_PROVIDER.FIRST_NAME LIKE '%" . $search_text . "%')";
}

$PK_USER_MASTER = $_GET['master_id'];

$ALL_APPOINTMENT_QUERY = "SELECT
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
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            DOA_SCHEDULING_CODE.UNIT,
                            GROUP_CONCAT(DISTINCT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME)) SEPARATOR ', ') AS SERVICE_PROVIDER_NAME,
                            GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ', ') AS CUSTOMER_NAME
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
                        WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
                        $appointment_type
                        AND DOA_APPOINTMENT_MASTER.STATUS = 'A'
                        AND DOA_USER_MASTER.PK_USER_MASTER = $PK_USER_MASTER
                        AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('NORMAL', 'AD-HOC', 'GROUP') 
                        $search
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC";

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

<table id="myTable" class="table table-striped border" data-page-length='50'>
    <thead>
        <tr>
            <th data-type="number" class="sortable" style="cursor: pointer">No</i></th>
            <th data-type="string" class="sortable" style="cursor: pointer">Customer</th>
            <th data-type="string" class="sortable" style="cursor: pointer">Enrollment ID</th>
            <th data-type="string" class="sortable" style="text-align: left;">Apt #</th>
            <th data-type="number" class="sortable" style="cursor: pointer">Serial No</i></th>
            <th data-type="string" class="sortable" style="cursor: pointer"><?= $service_provider_title ?></th>
            <th data-type="string" class="sortable" style="cursor: pointer">Day</th>
            <th data-date data-order class="sortable" style="cursor: pointer">Date</th>
            <th data-type="string" class="sortable" style="cursor: pointer">Time</th>
            <th data-type="string" class="sortable" style="cursor: pointer">Comment & Uploads</th>
            <th>Paid</th>
            <th>Status</th>
            <th>Completed</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody id="apt_tbody">
        <?php
        $service_code_array = [];
        $i = $page_first_result + 1;
        $appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY, $page_first_result . ',' . $results_per_page);
        while (!$appointment_data->EOF) {
            $PK_APPOINTMENT_MASTER = $appointment_data->fields['PK_APPOINTMENT_MASTER'];
            $UNIT = $appointment_data->fields['UNIT'];
            $status_data = $db_account->Execute("SELECT DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_APPOINTMENT_STATUS_HISTORY.TIME_STAMP FROM DOA_APPOINTMENT_STATUS_HISTORY LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS=DOA_APPOINTMENT_STATUS_HISTORY.PK_APPOINTMENT_STATUS LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER=DOA_APPOINTMENT_STATUS_HISTORY.PK_USER WHERE PK_APPOINTMENT_MASTER = " . $PK_APPOINTMENT_MASTER);
            $CHANGED_BY = '';
            while (!$status_data->EOF) {
                $CHANGED_BY .= "(" . $status_data->fields['APPOINTMENT_STATUS'] . " by " . $status_data->fields['NAME'] . " at " . date('m-d-Y H:i:s A', strtotime($status_data->fields['TIME_STAMP'])) . ")<br>";
                $status_data->MoveNext();
            }
            $IMAGE_LINK = $appointment_data->fields['IMAGE'];
            $VIDEO_LINK = $appointment_data->fields['VIDEO'];
            $SERIAL_NUMBER = 0;
            if ($appointment_data->fields['APPOINTMENT_TYPE'] === 'NORMAL') {
                $SESSION_CREATED = getSessionCreatedCount($appointment_data->fields['PK_ENROLLMENT_SERVICE'], $appointment_data->fields['APPOINTMENT_TYPE']);
                $PK_ENROLLMENT_SERVICE = $appointment_data->fields['PK_ENROLLMENT_SERVICE'];
                $ENROLLMENT_ID = $appointment_data->fields['ENROLLMENT_ID'];
                $ENROLLMENT_NAME = $appointment_data->fields['ENROLLMENT_NAME'];
                $SERIAL_NUMBER = $appointment_data->fields['SERIAL_NUMBER'];
            } else {
                $SESSION_CREATED = getSessionCreatedCount($appointment_data->fields['APT_ENR_SERVICE'], $appointment_data->fields['APPOINTMENT_TYPE']);
                $PK_ENROLLMENT_SERVICE = $appointment_data->fields['APT_ENR_SERVICE'];
                $ENROLLMENT_ID = $appointment_data->fields['APT_ENR_ID'];
                $ENROLLMENT_NAME = $appointment_data->fields['APT_ENR_NAME'];
            }

            $enr_service_data = $db_account->Execute("SELECT NUMBER_OF_SESSION, SESSION_CREATED, SESSION_COMPLETED FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_SERVICE` = " . $PK_ENROLLMENT_SERVICE);
            if ($enr_service_data->RecordCount() > 0 && $appointment_data->fields['PK_APPOINTMENT_STATUS'] != 6) {
                if (isset($service_code_array[$PK_ENROLLMENT_SERVICE])) {
                    $service_code_array[$PK_ENROLLMENT_SERVICE] = $service_code_array[$PK_ENROLLMENT_SERVICE] - $UNIT;
                } else {
                    $service_code_array[$PK_ENROLLMENT_SERVICE] = getAllSessionCreatedCount($PK_ENROLLMENT_SERVICE);;
                }
            } ?>
            <tr onclick="$(this).next().slideToggle(); loadMedia(<?= $PK_APPOINTMENT_MASTER ?>)">
                <td><?= $i; ?></td>
                <td><?= $appointment_data->fields['CUSTOMER_NAME'] ?></td>
                <?php if (!empty($ENROLLMENT_ID) || !empty($ENROLLMENT_NAME)) { ?>
                    <td><?= $ENROLLMENT_ID . (($ENROLLMENT_NAME) ? ' - ' . $ENROLLMENT_NAME : '') . " || " . $appointment_data->fields['SERVICE_NAME'] . " || " . $appointment_data->fields['SERVICE_CODE'] ?></td>
                <?php } elseif (empty($appointment_data->fields['SERVICE_NAME']) && empty($appointment_data->fields['SERVICE_CODE'])) { ?>
                    <td><?= $appointment_data->fields['SERVICE_NAME'] . "  " . $appointment_data->fields['SERVICE_CODE'] ?></td>
                <?php } else { ?>
                    <td><?= $appointment_data->fields['SERVICE_NAME'] . " || " . $appointment_data->fields['SERVICE_CODE'] ?></td>
                <?php } ?>
                <td><?= (isset($service_code_array[$PK_ENROLLMENT_SERVICE]) && $appointment_data->fields['PK_APPOINTMENT_STATUS'] != 6) ? $service_code_array[$PK_ENROLLMENT_SERVICE] . '/' . $enr_service_data->fields['NUMBER_OF_SESSION'] : '' ?></td>
                <td><?= $SERIAL_NUMBER ?></td>
                <td><?= $appointment_data->fields['SERVICE_PROVIDER_NAME'] ?></td>
                <td><?= date('l', strtotime($appointment_data->fields['DATE'])) ?></td>
                <td><?= date('m/d/Y', strtotime($appointment_data->fields['DATE'])) ?></td>
                <td><?= date('h:i A', strtotime($appointment_data->fields['START_TIME'])) . " - " . date('h:i A', strtotime($appointment_data->fields['END_TIME'])) ?></td>
                <td style="cursor: pointer; vertical-align: middle; text-align: center;"><?php if ($appointment_data->fields['COMMENT'] != '' || $appointment_data->fields['INTERNAL_COMMENT'] != '' || $IMAGE_LINK != '' || $VIDEO_LINK != '' || $CHANGED_BY != '') { ?>
                        <button class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="loadMedia(<?= $PK_APPOINTMENT_MASTER ?>);">View</button> <?php } ?>
                </td>
                <td><?= ($appointment_data->fields['IS_PAID'] == 1) ? 'Paid' : 'Unpaid' ?></td>
                <td style="text-align: left; color: <?= $appointment_data->fields['APPOINTMENT_COLOR'] ?>">
                    <?= $appointment_data->fields['APPOINTMENT_STATUS'] ?>&nbsp;
                    <?php if ($appointment_data->fields['IS_CHARGED'] == 1) { ?>
                        <i class="ti-money"></i>
                    <?php } ?>
                </td>
                <td style="text-align: center;">
                    <?php if ($appointment_data->fields['PK_APPOINTMENT_STATUS'] == 6 && $appointment_data->fields['IS_CHARGED'] == 1) { ?>
                        <i class="fa fa-check-circle" style="font-size:25px;color:red;"></i>
                    <?php } elseif ($appointment_data->fields['PK_APPOINTMENT_STATUS'] == 2) { ?>
                        <i class="fa fa-check-circle" style="font-size:25px;color:#35e235;"></i>
                    <?php } else { ?>
                        <a href="all_schedules.php?id=<?= $PK_APPOINTMENT_MASTER ?>&action=complete" data-id="<?= $PK_APPOINTMENT_MASTER ?>" onclick='confirmComplete($(this));return false;'><i class="fa fa-check-circle" style="font-size:25px;color:#a9b7a9;"></i></a>
                    <?php } ?>
                </td>
                <td>
                    <?php /*if(empty($ENROLLMENT_ID)) { */ ?><!--
                        <a href="create_appointment.php?type=ad_hoc&id=<?php /*=$PK_APPOINTMENT_MASTER*/ ?>"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <?php /*} else { */ ?>
                        <a href="add_schedule.php?id=<?php /*=$PK_APPOINTMENT_MASTER*/ ?>"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <?php /*} */ ?>
                    <a href="copy_schedule.php?id=<?php /*=$PK_APPOINTMENT_MASTER*/ ?>"><i class="fa fa-copy"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-->
                    <a href="javascript:;" onclick="deleteThisAppointment(<?= $PK_APPOINTMENT_MASTER ?>)"><i class="fa fa-trash"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <?php if ($type == 'cancelled' && ($enr_service_data->RecordCount() > 0 && ($enr_service_data->fields['NUMBER_OF_SESSION'] != $SESSION_CREATED))) { ?>
                        <a href="all_schedules.php?id=<?= $PK_APPOINTMENT_MASTER ?>" onclick='ConfirmScheduled(<?= $PK_APPOINTMENT_MASTER ?>,<?= $PK_ENROLLMENT_SERVICE ?>);' style="font-size: 18px"><i class="far fa-calendar-check"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <?php } ?>
                </td>
            </tr>
            <tr style="display: none">
                <td style="vertical-align: middle; text-align: center;" colspan="14">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" style="color: red;">Comments (Visual for client)</label>
                                <textarea class="form-control" name="COMMENT" rows="3"><?= $appointment_data->fields['COMMENT'] ?></textarea><span><?= $CHANGED_BY ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Internal Comment</label>
                                <textarea class="form-control" name="INTERNAL_COMMENT" rows="3"><?= $appointment_data->fields['INTERNAL_COMMENT'] ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div id="media_div_<?= $PK_APPOINTMENT_MASTER ?>">

                    </div>
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
                <li><a href="javascript:;" onclick="showAppointment(1, '<?= $type ?>')">&laquo;</a></li>
                <li><a href="javascript:;" onclick="showAppointment(<?= ($page - 1) ?>, '<?= $type ?>')">&lsaquo;</a></li>
            <?php }
            for ($page_count = 1; $page_count <= $number_of_page; $page_count++) {
                if ($page_count == $page || $page_count == ($page + 1) || $page_count == ($page - 1) || $page_count == $number_of_page) {
                    echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="javascript:;" onclick="showAppointment(' . $page_count . ', \'' . $type . '\')">' . $page_count . ' </a></li>';
                } elseif ($page_count == ($number_of_page - 1)) {
                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                } else {
                    echo '<li><a class="hidden" href="javascript:;" onclick="showAppointment(' . $page_count . ', \'' . $type . '\')">' . $page_count . ' </a></li>';
                }
            }
            if ($page < $number_of_page) { ?>
                <li><a href="javascript:;" onclick="showAppointment(<?= ($page + 1) ?>, '<?= $type ?>')">&rsaquo;</a></li>
                <li><a href="javascript:;" onclick="showAppointment(<?= $number_of_page ?>, '<?= $type ?>')">&raquo;</a></li>
            <?php } ?>
        </ul>
    </div>
</div>

<script>
    function deleteThisAppointment(PK_APPOINTMENT_MASTER) {
        Swal.fire({
            title: "Are you sure?",
            text: "Deleting this Appointment will not revert back.",
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
                        type: 'normal',
                        PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                    },
                    success: function(data) {
                        window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER + '&tab=appointment';
                    }
                });
            } else {
                Swal.fire({
                    title: "Cancelled",
                    text: "Your appointment is safe :)",
                    icon: "error"
                });
            }
        });
    }

    function ConfirmScheduled(PK_APPOINTMENT_MASTER, PK_ENROLLMENT_SERVICE) {
        var conf = confirm("Are you sure you want to Schedule this appointment?");
        if (conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'scheduleAppointment',
                    PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER,
                    PK_ENROLLMENT_SERVICE: PK_ENROLLMENT_SERVICE
                },
                success: function(data) {
                    window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER + '&tab=appointment';
                }
            });
        }
    }
</script>

<script>
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
    function loadMedia(PK_APPOINTMENT_MASTER) {
        if (PK_APPOINTMENT_MASTER) {
            $.ajax({
                url: "ajax/get_media.php",
                type: "POST",
                data: {
                    PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                },
                async: false,
                cache: false,
                success: function(result) {
                    $('#media_div_' + PK_APPOINTMENT_MASTER).html(result);
                }
            });
        }
    }
</script>