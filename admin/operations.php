<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4]) ){
    header("location:../login.php");
    exit;
}

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$title = "Appointments";

if (empty($_GET)) {
    $appointment_time = " AND DOA_APPOINTMENT_MASTER.DATE <= '" . date('Y-m-d') . "'";
} else {
    $appointment_time = "";
}

if (isset($_GET['SERVICE_PROVIDER_ID']) && $_GET['SERVICE_PROVIDER_ID'] != '') {
    $SERVICE_PROVIDER_ID = $_GET['SERVICE_PROVIDER_ID'];
} else {
    $SERVICE_PROVIDER_ID = 0;
}

$search_text = '';
$search='';
$SPECIFIC_DATE='';
$FROM_DATE='';
$END_DATE='';
$date_selection = '';
if (!empty($_GET['DATE_SELECTION'])) {
    if ($_GET['DATE_SELECTION'] == 1) {
        $date_selection = 1;
        $SPECIFIC_DATE = date('Y-m-d');
        if ($SERVICE_PROVIDER_ID > 0) {
            $search_text = $_GET['SERVICE_PROVIDER_ID'];
            $search = " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_APPOINTMENT_MASTER.DATE = '$SPECIFIC_DATE'";
        } else {
            $search = " AND DOA_APPOINTMENT_MASTER.DATE = '$SPECIFIC_DATE'";
        }
    } else if ($_GET['DATE_SELECTION'] == 2) {
        $date_selection = 1;
        $SPECIFIC_DATE = date('Y-m-d', strtotime("-1 days"));
        if ($SERVICE_PROVIDER_ID > 0) {
            $search_text = $SERVICE_PROVIDER_ID;
            $search = " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_APPOINTMENT_MASTER.DATE = '$SPECIFIC_DATE'";
        } else {
            $search = " AND DOA_APPOINTMENT_MASTER.DATE = '$SPECIFIC_DATE'";
        }
    } else if ($_GET['DATE_SELECTION'] == 3) {
        $date_selection = 3;
        [$START_DATE, $END_DATE] = currentWeekRange(date('Y-m-d'));
        if ($SERVICE_PROVIDER_ID > 0) {
            $search_text = $SERVICE_PROVIDER_ID;
            $search = " DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_APPOINTMENT_MASTER.DATE >= '$START_DATE' AND DOA_APPOINTMENT_MASTER.DATE <= '$END_DATE'";
        } else {
            $search = " AND DOA_APPOINTMENT_MASTER.DATE >= '$START_DATE' AND DOA_APPOINTMENT_MASTER.DATE <= '$END_DATE'";
        }
    } else if ($_GET['DATE_SELECTION'] == 4) {
        $date_selection = 4;
        $SPECIFIC_DATE = date('Y-m-d', strtotime($_GET['SPECIFIC_DATE']));
        if ($SERVICE_PROVIDER_ID > 0) {
            $search_text = $SERVICE_PROVIDER_ID;
            $search = " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_APPOINTMENT_MASTER.DATE = '$SPECIFIC_DATE'";
        } else {
            $search = " AND DOA_APPOINTMENT_MASTER.DATE = '$SPECIFIC_DATE'";
        }
    } else if ($_GET['DATE_SELECTION'] == 5) {
        $date_selection = 5;
        $FROM_DATE = date('Y-m-d', strtotime($_GET['FROM_DATE']));
        $END_DATE = date('Y-m-d', strtotime($_GET['END_DATE']));
        if ($SERVICE_PROVIDER_ID > 0) {
            $search_text = $SERVICE_PROVIDER_ID;
            $search = " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '$FROM_DATE' AND '$END_DATE'";
        } else {
            $search_text = '';
            $search = " AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '$FROM_DATE' AND '$END_DATE'";
        }
    }
    else if ($_GET['DATE_SELECTION'] == 6) {
        $date_selection = 6;
        $START_DATE = date('Y-m-d', strtotime('-1 year'));
        $END_DATE = date('Y-m-d');
        if ($SERVICE_PROVIDER_ID > 0) {
            $search_text = $SERVICE_PROVIDER_ID;
            $search = " DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_APPOINTMENT_MASTER.DATE >= '$START_DATE' AND DOA_APPOINTMENT_MASTER.DATE <= '$END_DATE'";
        } else {
            $search = " AND DOA_APPOINTMENT_MASTER.DATE >= '$START_DATE' AND DOA_APPOINTMENT_MASTER.DATE <= '$END_DATE'";
        }
    }
} elseif ($SERVICE_PROVIDER_ID > 0) {
    $search = " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID;
}

if (!empty($_GET['date'])) {
    if ($_GET['date'] == 'today') {
        $search = " AND DOA_APPOINTMENT_MASTER.DATE = '".date('Y-m-d')."'";
    } else if ($_GET['date'] == 'yesterday') {
        $search = " AND DOA_APPOINTMENT_MASTER.DATE = '".date('Y-m-d', strtotime('-1 day'))."'";
    } else if ($_GET['date'] == 'earlier') {
        $search = " AND DOA_APPOINTMENT_MASTER.DATE < '".date('Y-m-d')."' AND DOA_APPOINTMENT_MASTER.IS_PAID = 0";
    }
}

$date_start = '';
$date_end = '';

$appointment_status = empty($_GET['appointment_status']) ? '1, 2, 3, 5, 7, 8' : $_GET['appointment_status'];

if (!empty($_GET['FROM_DATE'])) {
    $date_start = date('Y-m-d', strtotime($_GET['FROM_DATE']));
}
if (!empty($_GET['END_DATE'])) {
    $date_end = date('Y-m-d', strtotime($_GET['END_DATE']));
}

$ALL_APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
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
                            DOA_APPOINTMENT_MASTER.NO_SHOW,
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            GROUP_CONCAT(DISTINCT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME)) SEPARATOR ',') AS SERVICE_PROVIDER_NAME,
                            GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ',') AS CUSTOMER_NAME
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
                        AND DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN (1, 3, 4, 5, 7, 8)
                        AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('GROUP', 'NORMAL', 'AD-HOC')
                        AND DOA_APPOINTMENT_MASTER.STATUS = 'A'
                        $appointment_time
                        $search
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC";

$query = $db_account->Execute($ALL_APPOINTMENT_QUERY);

$number_of_result =  $query->RecordCount();
$number_of_page = ceil ($number_of_result / $results_per_page);
if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;

function currentWeekRange($date): array
{
    $ts = strtotime($date);
    $start = (date('w', $ts) == 0) ? $ts : strtotime('last sunday', $ts);
    return array(date('Y-m-d', $start), date('Y-m-d', strtotime('next saturday', $start)));
}

?>

<!DOCTYPE html>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
<style>
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
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
            </div>
            <form id="search_form" class="form-horizontal" action="" method="get">
            <div class="row">
                <div class="col-2">
                    <div class="form-group">
                        <select class="form-control" name="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID">
                            <option value="">Select <?=$service_provider_title?></option>
                            <?php
                            $selected_service_provider = '';
                            $row = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER=DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND ACTIVE=1 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY NAME");
                            while (!$row->EOF) { ?>
                                <option value="<?=$row->fields['PK_USER']?>" <?=($row->fields['PK_USER']==$SERVICE_PROVIDER_ID)?'selected':''?>><?=$row->fields['NAME']?></option>
                            <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
                <div class="col-3">
                    <div class="d-flex justify-content-center align-items-center"">
                    <?php $today_count = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS TODAY_COUNT FROM DOA_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID) AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND DOA_APPOINTMENT_MASTER.DATE = '".date('Y-m-d')."'"); ?>
                        <a type="button" id="today" style="color: <?php echo $_GET['date']=='today' ? 'black' : 'white'; ?>" class="btn btn-info" href="operations.php?date=today"> Today (<?=($today_count->RecordCount() > 0) ? $today_count->fields['TODAY_COUNT'] : 0?>)</a>
                    <?php $yesterday_count = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS YESTERDAY_COUNT FROM DOA_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID) AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND DOA_APPOINTMENT_MASTER.DATE = '".date('Y-m-d', strtotime('-1 day'))."'"); ?>
                        <a type="button" id="yesterday" style="color: <?php echo $_GET['date']=='yesterday' ? 'black' : 'white'; ?>" class="btn btn-info d-none d-lg-block m-l-10" href="operations.php?date=yesterday"> Yesterday (<?=($yesterday_count->RecordCount() > 0) ? $yesterday_count->fields['YESTERDAY_COUNT'] : 0?>)</a>
                    <?php
                    [$START_DATE, $END_DATE] = currentWeekRange(date('Y-m-d'));
                    $earlier_count = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS EARLIER_COUNT FROM DOA_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID) AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS IN (1, 3, 4, 5, 7, 8) AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('GROUP', 'NORMAL', 'AD-HOC') AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.DATE >= '$START_DATE' AND DOA_APPOINTMENT_MASTER.DATE <= '$END_DATE'"); ?>
                        <a type="button" id="earlier" style="color: <?php echo $_GET['date']=='earlier' ? 'black' : 'white'; ?>" class="btn btn-info d-none d-lg-block m-l-10" href="operations.php?DATE_SELECTION=3"> Week (<?=($earlier_count->RecordCount() > 0) ? $earlier_count->fields['EARLIER_COUNT'] : 0?>)</a>
                    </div>
                </div>
                <div class="col-2">
                    <div class="form-group">
                        <select class="form-control" name="DATE_SELECTION" id="DATE_SELECTION" onchange="selectDate(this)">
                            <option value="">Select Date</option>
                            <!--<option value="1">Today</option>
                            <option value="2">Yesterday</option>
                            <option value="3">This week</option>-->
                            <option value="4" <?php if (isset($_GET['DATE_SELECTION']) && $_GET['DATE_SELECTION'] == 4) {echo 'selected = "selected"';}?>>Specific Date</option>
                            <option value="5" <?php if (isset($_GET['DATE_SELECTION']) && $_GET['DATE_SELECTION'] == 5) {echo 'selected = "selected"';}?>>Date Range</option>
                            <option value="6" <?php if (isset($_GET['DATE_SELECTION']) && $_GET['DATE_SELECTION'] == 6) {echo 'selected = "selected"';}?>>Earlier</option>
                        </select>
                    </div>
                </div>
                <div class="col-2 specific_date" style="display: <?php echo (isset($_GET['DATE_SELECTION']) && $_GET['DATE_SELECTION'] == 4) ? 'block' : 'none'?>">
                    <div class="form-group">
                        <input type="text" id="SPECIFIC_DATE" name="SPECIFIC_DATE" placeholder="Specific Date" class="form-control datepicker-past" value="<?=($SPECIFIC_DATE == '' || $SPECIFIC_DATE == '0000-00-00')?'':date('m/d/Y', strtotime($SPECIFIC_DATE))?>">
                    </div>
                </div>
                <div class="col-1 from_date" style="display: <?php echo (isset($_GET['DATE_SELECTION']) && $_GET['DATE_SELECTION'] == 5) ? 'block' : 'none'?>">
                    <div class="form-group">
                        <input type="text" id="FROM_DATE" name="FROM_DATE" placeholder="From Date" class="form-control datepicker-normal" value="<?=($date_start == '' || $date_start == '0000-00-00')?'':date('m/d/Y', strtotime($date_start))?>">
                    </div>
                </div>
                <div class="col-1 end_date" style="display: <?php echo (isset($_GET['DATE_SELECTION']) && $_GET['DATE_SELECTION'] == 5) ? 'block' : 'none'?>">
                    <div class="form-group">
                        <input type="text" id="END_DATE" name="END_DATE" placeholder="To Date" class="form-control datepicker-normal" value="<?=($date_end == '' || $date_end == '0000-00-00')?'':date('m/d/Y', strtotime($date_end))?>">
                    </div>
                </div>
                <div class="col-1">
                <button class="btn btn-info waves-effect waves-light m-r-10 text-white input-group-btn m-b-1" type="submit"><i class="fa fa-search"></i></button>
                </div>
            </div>
            </form>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <?php if(in_array('Operations Edit', $PERMISSION_ARRAY)){ ?>
                            <div style="margin-left: -12px; margin-bottom: 10px"><button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="markAllComplete()"><i class="ti-check-box"></i> Completed</button></div>
                            <?php } ?>
                            <div id="list"  class="card-body">
                                <table id="" class="table table-striped border" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th style="width: 3%"><input type="checkbox" onClick="toggle(this)" /></th>
                                        <th data-type="string" class="sortable" style="cursor: pointer; width: 20%">Customer</th>
                                        <th data-type="string" class="sortable" style="cursor: pointer; width: 20%">Enrollment ID</th>
                                        <th data-type="string" class="sortable" style="cursor: pointer; width: 20%"><?=$service_provider_title?></th>
                                        <th data-type="string" class="sortable" style="cursor: pointer; width: 10%">Day</th>
                                        <th data-type="datetime" class="sortable" style="cursor: pointer; width: 10%">Date</th>
                                        <th data-type="time" class="sortable" style="cursor: pointer; width: 10%">Time</th>
                                        <th style="width: 7%">Paid</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=$page_first_result+1;
                                    $appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY, $page_first_result . ',' . $results_per_page);
                                    $current_date = date('Y-m-d');

                                    while (!$appointment_data->EOF) {
                                        $date = $appointment_data->fields['DATE'];
                                        $no_show = $appointment_data->fields['NO_SHOW'];
                                        $pk_appointment_status = $appointment_data->fields['PK_APPOINTMENT_STATUS'];
                                        ?>
                                        <tr style="background-color: <?=($date==$current_date)?'limegreen': ( $no_show=='Charge' || $no_show=='No Charge' ? 'yellow' : ( $pk_appointment_status==6 ? 'red' : '' ) );?>">
                                            <td>
                                                <?php if ($appointment_data->fields['CUSTOMER_NAME']) { ?>
                                                    <label><input type="checkbox" name="PK_APPOINTMENT_MASTER[]" class="PK_APPOINTMENT_MASTER" value="<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>"></label>
                                                <?php } else {
                                                    if(in_array('Operations Edit', $PERMISSION_ARRAY)) { ?>
                                                    <a href="javascript:" onclick='ConfirmDelete(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);'><i class="fa fa-trash"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php }
                                                            } ?>
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
                                            <td><?=date('m/d/Y', strtotime($appointment_data->fields['DATE']))?></td>
                                            <td><?=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
                                            <td><?=($appointment_data->fields['IS_PAID'] == 1)?'Paid':'Unpaid'?></td>
                                        </tr>
                                        <?php $appointment_data->MoveNext();
                                        $i++; } ?>
                                    </tbody>
                                </table>

                                <div class="center">
                                    <div class="pagination outer">
                                        <ul>
                                            <?php if ($page > 1) { ?>
                                                <li><a href="operations.php?DATE_SELECTION=<?=$date_selection?>&FROM_DATE=<?=$date_start?>&END_DATE=<?=$date_end?>&appointment_status=<?=$appointment_status?>&page=1">&laquo;</a></li>
                                                <li><a href="operations.php?DATE_SELECTION=<?=$date_selection?>&FROM_DATE=<?=$date_start?>&END_DATE=<?=$date_end?>&appointment_status=<?=$appointment_status?>&page=<?=($page-1)?>">&lsaquo;</a></li>
                                            <?php }
                                            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                                                if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                                                    echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="operations.php?DATE_SELECTION='.$date_selection.'&FROM_DATE='.$date_start.'&END_DATE='.$date_end.'&appointment_status=' . $appointment_status . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                } elseif ($page_count == ($number_of_page-1)){
                                                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                } else {
                                                    echo '<li><a class="hidden" href="operations.php?DATE_SELECTION='.$date_selection.'&FROM_DATE='.$date_start.'&END_DATE='.$date_end.'&appointment_status=' . $appointment_status . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                }
                                            }
                                            if ($page < $number_of_page) { ?>
                                                <li><a href="operations.php?DATE_SELECTION=<?=$date_selection?>&FROM_DATE=<?=$date_start?>&END_DATE=<?=$date_end?>&appointment_status=<?=$appointment_status?>&page=<?=($page+1)."&".$search?>">&rsaquo;</a></li>
                                                <li><a href="operations.php?DATE_SELECTION=<?=$date_selection?>&FROM_DATE=<?=$date_start?>&END_DATE=<?=$date_end?>&appointment_status=<?=$appointment_status?>&page=<?=$number_of_page?>">&raquo;</a></li>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function selectDate(param) {
        let Date = parseInt($(param).val());

        if (Date === 1) {
            $('.from_date').hide();
            $('.end_date').hide();
            $('.specific_date').hide();
        } else if (Date === 2) {
            $('.from_date').hide();
            $('.end_date').hide();
            $('.specific_date').hide();
        } else if (Date === 3) {
            $('.from_date').hide();
            $('.end_date').hide();
            $('.specific_date').hide();
        } else if (Date === 4) {
            $('.from_date').hide();
            $('.end_date').hide();
            $('.specific_date').slideDown();
        } else if (Date === 5) {
            $('.from_date').slideDown();
            $('.end_date').slideDown();
            $('.specific_date').hide();
        }
    }

    $('.datepicker-past').datepicker({
        onSelect: function () {
            $('#IS_SELECTED').val(1);
            $("#search_form").submit();
        },
        format: 'mm/dd/yyyy',
    });

    $(document).ready(function(){
        $("#SPECIFIC_DATE").datepicker({
            format: 'mm/dd/yyyy',
            maxDate: 0
        });
        $("#FROM_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(selected) {
                $("#END_DATE").datepicker("option","minDate", selected);
                $("#FROM_DATE, #END_DATE").trigger("change");
            }
        });
        $("#END_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(selected) {
                $("#FROM_DATE").datepicker("option","maxDate", selected)
            }
        });
    });

    $("#myTable").dataTable({
        "searching": true
    });


    $(function () {
        let table = $('#myTable').DataTable();

        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {

                let eventType   = $('#SERVICE_PROVIDER_ID').val();
                let startDate = $('#START_DATE').val();
                let endDate = $('#END_DATE').val();

                let eventTypeVal  = data[5];
                let startedAt = data[7] || 0;
                let endedAt = data[7] || 0;


                if ((eventType === "" || eventTypeVal.includes(eventType)) && (startDate == "" || moment(startedAt).isSameOrAfter(startDate)) && (endDate == "" || moment(endedAt).isSameOrBefore(endDate)))
                {
                    return true;
                }
                return false;
            }
        );

        $("#SERVICE_PROVIDER_ID, #START_DATE, #END_DATE").change(function (e) {
            table.draw();
        });

        table.draw();
    });

/*    function ConfirmDelete(PK_APPOINTMENT_MASTER)
    {
        var conf = confirm("Are you sure you want to delete this appointment?");
        if(conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {FUNCTION_NAME: 'deleteAppointment', PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER},
                success: function (data) {
                    window.location.href = 'operations.php';
                }
            });
        }
    }*/

    function ConfirmDelete(PK_APPOINTMENT_MASTER)
    {
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
                    data: {FUNCTION_NAME: 'deleteAppointment', PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER},
                    success: function (data) {
                        window.location.href = 'operations.php';
                    }
                });
            }
        });
    }

    function editpage(id){
        window.location.href = "add_schedule.php?id="+id;
    }

    function toggle(source) {
        var checkboxes = document.querySelectorAll('input[type="checkbox"]');
        for (var i = 0; i < checkboxes.length; i++) {
            if (checkboxes[i] != source)
                checkboxes[i].checked = source.checked;
        }
    }

    function confirmComplete(anchor)
    {
        let conf = confirm("Do you want to mark this appointment as completed?");
        if(conf)
            window.location=anchor.attr("href");
    }

    function markAllComplete()
    {
        let PK_APPOINTMENT_MASTER = [];
        $(".PK_APPOINTMENT_MASTER:checked").each(function() {
            PK_APPOINTMENT_MASTER.push($(this).val());
        });

        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: {FUNCTION_NAME: 'markAllAppointmentCompleted', PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER},
            success:function (data) {
                window.location="operations.php";
            }
        });
    }


</script>

// start sorting
<script>
    $(function() {
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
//end sorting
</body>
</html>