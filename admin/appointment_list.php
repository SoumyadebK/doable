<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
$LOCATION_ARRAY = explode(',', $_SESSION['DEFAULT_LOCATION_ID']);

$status_check = empty($_GET['status']) ? '' : $_GET['status'];
$appointment_time = ' ';
if ($status_check == 'previous'){
    $appointment_time = " AND DOA_APPOINTMENT_MASTER.DATE < '".date('Y-m-d')."'";
} elseif ($status_check == 'future') {
    $appointment_time = " AND DOA_APPOINTMENT_MASTER.DATE > '".date('Y-m-d')."'";
} elseif (empty($_GET['START_DATE']) && empty($_GET['END_DATE']) && empty($_GET['search_text'])) {
    $appointment_time = " AND DOA_APPOINTMENT_MASTER.DATE = '".date('Y-m-d')."'";
}

$appointment_status = empty($_GET['appointment_status']) ? '1, 2, 3, 5, 7, 8' : $_GET['appointment_status'];

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$START_DATE = ' ';
$END_DATE = ' ';
if (!empty($_GET['START_DATE'])) {
    $START_DATE = " AND DOA_APPOINTMENT_MASTER.DATE >= '".date('Y-m-d', strtotime($_GET['START_DATE']))."'";
}
if (!empty($_GET['END_DATE'])) {
    $END_DATE = " AND DOA_APPOINTMENT_MASTER.DATE <= '".date('Y-m-d', strtotime($_GET['END_DATE']))."'";
}

$search_text = '';
$search = $START_DATE.$END_DATE. ' ';
if (!empty($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = $START_DATE.$END_DATE." AND (DOA_ENROLLMENT_MASTER.ENROLLMENT_ID LIKE '%".$search_text."%' OR CUSTOMER.FIRST_NAME LIKE '%".$search_text."%' OR SERVICE_PROVIDER.FIRST_NAME LIKE '%".$search_text."%' OR CUSTOMER.LAST_NAME LIKE '%".$search_text."%' OR SERVICE_PROVIDER.LAST_NAME LIKE '%".$search_text."%' OR CUSTOMER.EMAIL_ID LIKE '%".$search_text."%' OR CUSTOMER.PHONE LIKE '%".$search_text."%')";
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
    $title = "All Appointment";
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
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
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
                        AND DOA_APPOINTMENT_MASTER.STATUS = 'A'
                        $standing_cond
                        $appointment_time
                        $search
                        $standing_group
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC";

$query = $db_account->Execute($ALL_APPOINTMENT_QUERY);

$number_of_result =  $query->RecordCount();
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;

?>

<!DOCTYPE html>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
<style>
    table th{
        font-weight:bold;
    }
</style>
<style>
    .fc-basic-view .fc-day-number {
        display: table-cell;
    }

    .modal-header {
        display: block;
    }

    .modal-dialog {
        max-width: 1200px;
        width: 1100px;
        margin: 2rem auto;
    }

    .fc-time-grid .fc-slats td {
        height: 2.5em;
    }

    .SumoSelect {
        width: 100%;
    }
    #add_buttons {
        z-index: 500;
    }

    /* Table sort indicators */

    th.sortable {
        position: relative;
        cursor: pointer;
    }

    th.sortable::after {
        font-family: FontAwesome;
        content: "\f0dc";
        position: absolute;
        right: 8px;
        color: #999;
    }

    th.sortable.asc::after {
        content: "\f0d8";
    }

    th.sortable.desc::after {
        content: "\f0d7";
    }

    th.sortable:hover::after {
        color: #333;
    }

</style>
<html lang="en">
<?php require_once('../includes/header.php');?>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content">
            <div class="row" >
                <div id="add_buttons" class="d-flex justify-content-center align-items-center" style="position: fixed; bottom: 0">
                    <!--<button type="button" id="group_class" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=group_class'"><i class="fa fa-plus-circle"></i> Group Class</button>
                    <button type="button" id="int_app" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=int_app'"><i class="fa fa-plus-circle"></i> INT APP</button>
                    <button type="button" id="appointment" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=appointment'"><i class="fa fa-plus-circle"></i> Appointment</button>
                    <button type="button" id="standing" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=standing'"><i class="fa fa-plus-circle"></i> Standing</button>
                    <button type="button" id="ad_hoc" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=ad_hoc'"><i class="fa fa-plus-circle"></i> Ad-hoc Appointment</button>-->
                    <button type="button" id="appointments" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="showMessage()"><i class="fa fa-plus-circle"></i> Appointments</button>
                    <button type="button" id="operations" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='operations.php'"><i class="ti-layers-alt"></i> <?=$operation_tab_title?></button>
                </div>
            </div>

            <form class="form-material form-horizontal" id="search_form" action="" method="get">
                <div class="row page-titles">
                    <div class="col-md-2 align-self-center">
                        <?php if ($status_check=='previous') { ?>
                            <h4 class="text-themecolor">Previous Appointments</h4>
                        <?php } elseif ($status_check=='future') { ?>
                            <h4 class="text-themecolor">Future Appointments</h4>
                        <?php } else { ?>
                            <h4 class="text-themecolor"><?=$title?></h4>
                        <?php } ?>
                    </div>

                    <?php if (empty($_GET['status']) || $status_check=='future') { ?>
                        <div class="col-md-2 align-self-center">
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='appointment_list.php?status=previous'">Previous Appointments</button>
                        </div>
                    <?php } elseif ($status_check=='previous') { ?>
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
                                    <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS'];?>" <?=($row->fields['PK_APPOINTMENT_STATUS'] == $appointment_status)?"selected":""?>><?=$row->fields['APPOINTMENT_STATUS']?></option>
                                <?php $row->MoveNext(); } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-5">
                        <div class="input-group">
                            <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" placeholder="Start Date" value="<?=!empty($_GET['START_DATE'])?$_GET['START_DATE']:''?>">&nbsp;&nbsp;&nbsp;&nbsp;
                            <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" placeholder="End Date" value="<?=!empty($_GET['END_DATE'])?$_GET['END_DATE']:''?>">&nbsp;&nbsp;&nbsp;&nbsp;
                            <input class="form-control" type="text" id="search_text" name="search_text" placeholder="Search.." value="<?=$search_text?>">
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
                                <table class="table <?=($standing == 0) ? 'table-striped' : ''?> border" data-page-length='50'>
                                    <thead>
                                        <tr>
                                            <th data-type="number" class="sortable" style="cursor: pointer">No</th>
                                            <th data-type="number" class="sortable" style="cursor: pointer">Service Name</th>
                                            <th data-type="string" class="sortable" style="cursor: pointer">Customer</th>
                                            <th data-type="string" class="sortable" style="cursor: pointer">Enrollment ID</th>
                                            <th data-type="string" class="sortable" style="cursor: pointer"><?=$service_provider_title?></th>
                                            <th data-type="string" class="sortable" style="cursor: pointer">Day</th>
                                            <th data-date data-order class="sortable" style="cursor: pointer">Date</th>
                                            <th data-type="string" class="sortable" style="cursor: pointer">Time</th>
                                            <th>Paid</th>
                                            <th>Completed</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>


                                    <tbody>
                                    <?php
                                    $i=$page_first_result+1;
                                    $appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY, $page_first_result . ',' . $results_per_page);
                                    while (!$appointment_data->EOF) { if ($standing == 0) { ?>
                                        <tr>
                                    <?php } else { ?>
                                        <tr class="header" onclick="showStandingAppointmentDetails(this, <?=$appointment_data->fields['STANDING_ID']?>, <?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>)" style="cursor: pointer;">
                                    <?php } ?>
                                            <td><?=$i;?></td>
                                            <td><?=(($appointment_data->fields['APPOINTMENT_TYPE'] == 'NORMAL') ? 'Private Session' : (($appointment_data->fields['APPOINTMENT_TYPE'] == 'AD-HOC') ? 'Ad-Hoc' : 'Group Class'))?>
                                                <?php if ($appointment_data->fields['STANDING_ID'] > 0) { ?>
                                                    <span style="font-weight: bold; color: #1B72B8">(S)</span>
                                                <?php } ?>
                                            </td>
                                            <td><?=$appointment_data->fields['CUSTOMER_NAME']?></td>
                                            <?php if (!empty($appointment_data->fields['ENROLLMENT_ID']) || !empty($appointment_data->fields['ENROLLMENT_NAME'])) { ?>
                                                <td><?=(($appointment_data->fields['ENROLLMENT_NAME']) ? $appointment_data->fields['ENROLLMENT_NAME'].' - ' : '').$appointment_data->fields['ENROLLMENT_ID']." || ".$appointment_data->fields['SERVICE_NAME']." || ".$appointment_data->fields['SERVICE_CODE']?></td>
                                            <?php } elseif (empty($appointment_data->fields['SERVICE_NAME']) && empty($appointment_data->fields['SERVICE_CODE'])) { ?>
                                                <td><?=$appointment_data->fields['SERVICE_NAME']."  ".$appointment_data->fields['SERVICE_CODE']?></td>
                                            <?php } else { ?>
                                                <td><?=$appointment_data->fields['SERVICE_NAME']." || ".$appointment_data->fields['SERVICE_CODE']?></td>
                                            <?php } ?>
                                            <td><?=$appointment_data->fields['SERVICE_PROVIDER_NAME']?></td>
                                            <td><?=date('l', strtotime($appointment_data->fields['DATE']))?></td>

                                            <?php if ($standing == 0) { ?>
                                                <td><?=date('m/d/Y', strtotime($appointment_data->fields['DATE']))?></td>
                                            <?php } else { ?>
                                                <td><?=date('m/d/Y', strtotime($appointment_data->fields['BEGINNING_DATE']))?> - <?=date('m/d/Y', strtotime($appointment_data->fields['END_DATE']))?></td>&nbsp;&nbsp;&nbsp;
                                            <?php } ?>

                                            <td><?=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
                                            <td><?=($appointment_data->fields['IS_PAID'] == 1)?'Paid':'Unpaid'?></td>
                                            <td style="text-align: center;">
                                                <?php
                                                if ($appointment_data->fields['CUSTOMER_NAME'] && $standing == 0) {
                                                    if ($appointment_data->fields['PK_APPOINTMENT_STATUS'] == 6 && $appointment_data->fields['IS_CHARGED'] == 1){ ?>
                                                        <i class="fa fa-check-circle" style="font-size:25px;color:red;"></i>
                                                    <?php } elseif ($appointment_data->fields['PK_APPOINTMENT_STATUS'] == 2){ ?>
                                                        <i class="fa fa-check-circle" style="font-size:25px;color:#35e235;"></i>
                                                    <?php } else { ?>
                                                        <a href="javascript:" data-id="<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>" onclick='confirmComplete($(this));'><i class="fa fa-check-circle" style="font-size:25px;color:#a9b7a9;"></i></a>
                                                    <?php }
                                                } ?>
                                            </td>
                                            <td>
                                                <?php /*if(empty($appointment_data->fields['ENROLLMENT_ID'])) { */?><!--
                                                    <a href="create_appointment.php?type=ad_hoc&id=<?php /*=$appointment_data->fields['PK_APPOINTMENT_MASTER']*/?>"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php /*} else { */?>
                                                    <a href="add_schedule.php?id=<?php /*=$appointment_data->fields['PK_APPOINTMENT_MASTER']*/?>"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                --><?php /*} */?>
                                                <?php if ($standing == 0) { ?>
                                                    <a href="copy_schedule.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>"><i class="fa fa-copy"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                    <a href="javascript:" onclick='ConfirmDelete(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>, "normal");'><i class="fa fa-trash"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php } else { ?>
                                                    <a href="javascript:" onclick='ConfirmDelete(<?=$appointment_data->fields['STANDING_ID']?>, "standing");'><i class="fa fa-trash"></i></a>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <!--<tbody class="standing_list" style="display: none; background-color: #dee2e6;">

                                    </tbody>-->
                                    <?php $appointment_data->MoveNext();
                                    $i++; } ?>
                                    </tbody>
                                </table>

                                <div class="center">
                                    <div class="pagination outer">
                                        <ul>
                                            <?php if ($page > 1) { ?>
                                                <li><a href="appointment_list.php?status=<?=$status_check?>&appointment_status=<?=$appointment_status?>&page=1">&laquo;</a></li>
                                                <li><a href="appointment_list.php?status=<?=$status_check?>&appointment_status=<?=$appointment_status?>&page=<?=($page-1)?>">&lsaquo;</a></li>
                                            <?php }
                                            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                                                if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                                                    echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="appointment_list.php?status=' . $status_check . '&appointment_status=' . $appointment_status . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                } elseif ($page_count == ($number_of_page-1)){
                                                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                } else {
                                                    echo '<li><a class="hidden" href="appointment_list.php?status=' . $status_check . 'appointment_status=' . $appointment_status . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                }
                                            }
                                            if ($page < $number_of_page) { ?>
                                                <li><a href="appointment_list.php?status=<?=$status_check?>&appointment_status=<?=$appointment_status?>&page=<?=($page+1)?>">&rsaquo;</a></li>
                                                <li><a href="appointment_list.php?status=<?=$status_check?>&appointment_status=<?=$appointment_status?>&page=<?=$number_of_page?>">&raquo;</a></li>
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

<?php require_once('../includes/footer.php');?>

<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

<script>
    $(function () {
        startDate = $("#START_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(selected) {
                $("#END_DATE").datepicker("option","minDate", selected);
                $("#START_DATE, #END_DATE").trigger("change");
            }
        });
        $("#END_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(selected) {
                $("#START_DATE").datepicker("option","maxDate", selected)
            }
        });

        const ths = $("th");
        let sortOrder = 1;

        ths.on("click", function() {
            const rows = sortRows(this);
            rebuildTbody(rows);
            //updateClassName(this);
            sortOrder *= -1; //反転
        })

        function sortRows(th) {
            const rows = $.makeArray($('tbody > tr'));
            const col = th.cellIndex;
            const type = th.dataset.type;
            rows.sort(function(a, b) {
                return compare(a, b, col, type) * sortOrder;
            });
            return rows;
        }

        function compare(a, b, col, type) {
            let _a = a.children[col].textContent;
            let _b = b.children[col].textContent;
            if (type === "number") {
                _a *= 1;
                _b *= 1;
            } else if (type === "string") {
                //全て小文字に揃えている。toLowerCase()
                _a = _a.toLowerCase();
                _b = _b.toLowerCase();
            }

            if (_a < _b) {
                return -1;
            }
            if (_a > _b) {
                return 1;
            }
            return 0;
        }

        function rebuildTbody(rows) {
            const tbody = $("tbody");
            while (tbody.firstChild) {
                tbody.remove(tbody.firstChild);
            }

            let j;
            for (j=0; j<rows.length; j++) {
                tbody.append(rows[j]);
            }
        }

        /*function updateClassName(th) {
            let k;
            for (k=0; k<ths.length; k++) {
                ths[k].className = "";
            }
            th.className = sortOrder === 1 ? "asc" : "desc";
        }*/

    });

    function showMessage() {
        if(<?=count($LOCATION_ARRAY)?> === 1) {
            window.location.href = 'create_appointment.php';
        } else {
            swal("Select One Location!", "Only one location can be selected on top of the page in order to schedule an appointment.", "error");
        }
    }
</script>
<script>
    function Checktrim(str) {
        str = str.replace(/^\s+/, '');
        for (var i = str.length - 1; i >= 0; i--) {
            if (/\S/.test(str.charAt(i))) {
                str = str.substring(0, i + 1);
                break;
            }
        }
        return str;
    }
    function stringMonth(month) {

        if(month=="jan" || month=="Jan"){month=01;}
        else if(month=="feb" || month=="Feb"){month=02;}
        else if(month=="mar" || month=="Mar"){month=03;}
        else if(month=="apr" || month=="Apr"){month=04;}
        else if(month=="may" || month=="May"){month=05;}
        else if(month=="jun" || month=="Jun"){month=06;}
        else if(month=="jul" || month=="Jul"){month=07;}
        else if(month=="aug" || month=="Aug"){month=08;}
        else if(month=="sep" || month=="Sep"){month=09;}
        else if(month=="oct" || month=="Oct"){month=10;}
        else if(month=="nov" || month=="Nov"){month=11;}
        else{month=12;}


        return month;
    }

    function dateHeight(dateStr){


        if (Checktrim(dateStr) != ''  && Checktrim(dateStr) != '(none)' && (Checktrim(dateStr)).indexOf(',') > -1 ) {

            var frDateParts = Checktrim(dateStr).split(',');

            var day = frDateParts[0].substring(3) * 60 * 24;
            var strMonth=frDateParts[0].substring(0,3);
            var month = stringMonth(strMonth) * 60 * 24 * 31;
            var year = (frDateParts[1].trim()).substring(0,4) * 60 * 24 * 366;

            var x = day+month+year;


        } else {
            var x =0; //highest value posible
        }

        return x;
    }

    jQuery.fn.dataTableExt.oSort['data-date-asc'] = function(a, b) {
        var x = dateHeight(a) === 0 ? dateHeight(b)+1 : dateHeight(a) ;
        var y = dateHeight(b)=== 0 ? dateHeight(a)+1 : dateHeight(b);
        var z = ((x < y) ? -1 : ((x > y) ? 1 : 0));
        return z;
    };

    jQuery.fn.dataTableExt.oSort['data-date-desc'] = function(a, b) {
        var x = dateHeight(a);
        var y = dateHeight(b);
        var z = ((x < y) ? 1 : ((x > y) ? -1 : 0));
        return z;
    };




    var aoColumns = [];

    var $tableTh = $(".data-table th , .dataTable th");
    if($tableTh.length) {
        $tableTh.each(function(index,elem) {
            if($(elem).hasClass('sortable-false')) {
                aoColumns.push({"bSortable": false });
            } else if($(elem).attr('data-date') !== undefined) {
                aoColumns.push({"sType": "data-date" });
            }else{
                aoColumns.push(null);
            }
        });


    };



    if(aoColumns.length > 0) {

        var indexProperty=0;
        var valueProperty='asc';
        $('.data-table').find('th').each(function(index){


            if($(this).attr('data-order')!== undefined){
                indexProperty=index;
                valueProperty = $(this).attr('data-order') !== undefined? $(this).attr('data-order') : valueProperty;
            }});



        $('.data-table').dataTable({
            "aoColumns": aoColumns,
            "order":[[indexProperty,valueProperty]],
            "oLanguage": {
                "sSearch": "Keyword Search"
            },
            "dom": '<"top"<"row"<"component-4"<"dataTableAction">><"component-4"<"dataTableLength"l<"clear">>> <"component-4"<"dataTableFilter"f<"clear">>>>>rt<"bottom"ip<"clear">>',
            "fnDrawCallback": function(){DataTableTruncate.initTrigger();}
        });
    }


</script>
<script>
    var sortable = $('.sortable');

    sortable.on('click', function(){

        var sort = $(this);
        var asc = sort.hasClass('asc');
        var desc = sort.hasClass('desc');
        sortable.removeClass('asc').removeClass('desc');
        if (desc || (!asc && !desc)) {
            sort.addClass('asc');
        } else {
            sort.addClass('desc');
        }

    });
</script>
<script>
    function confirmComplete(param)
    {
        let conf = confirm("Do you want to mark this appointment as completed?");
        if (conf) {
            let PK_APPOINTMENT_MASTER = $(param).data('id');
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {FUNCTION_NAME: 'markAppointmentCompleted', PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER},
                success:function (data) {
                    if (data == 1){
                        $(param).closest('td').html('<span class="status-box" style="background-color: #ff0019">Completed</span>');
                    } else {
                        alert("Something wrong");
                    }
                }
            });
        }
    }

    function showStandingAppointmentDetails(param, STANDING_ID, PK_APPOINTMENT_MASTER) {
        $(param).nextUntil('tr.header').remove();
        $.ajax({
            url: "pagination/get_standing_appointment.php",
            type: 'GET',
            data: {STANDING_ID:STANDING_ID, PK_APPOINTMENT_MASTER:PK_APPOINTMENT_MASTER},
            success: function (result) {
                //$(param).nextUntil('tr.header').slideToggle();
                $(result).insertAfter($(param).closest('tr'));
            }
        });
    }

    function ConfirmDelete(PK_APPOINTMENT_MASTER, type)
    {
        swal({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {FUNCTION_NAME: 'deleteAppointment', type:type, PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER},
                    success: function (data) {
                        window.location.href = 'appointment_list.php';
                    }
                });
            }
        });
    }

    function selectStatus(param){
        var status = $(param).val();
        window.location.href = "appointment_list.php?appointment_status="+status;

    }
</script>
