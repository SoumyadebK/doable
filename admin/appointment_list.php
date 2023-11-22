<?php
require_once('../global/config.php');
$title = "All Customers";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$results_per_page = 100;

$START_DATE = ' ';
$END_DATE = ' ';
if (isset($_GET['START_DATE']) && $_GET['START_DATE'] != '') {
    $START_DATE = " AND DOA_APPOINTMENT_MASTER.DATE >= '".date('Y-m-d', strtotime($_GET['START_DATE']))."'";
}
if (isset($_GET['END_DATE']) && $_GET['END_DATE'] != '') {
    $END_DATE = " AND DOA_APPOINTMENT_MASTER.DATE <= '".date('Y-m-d', strtotime($_GET['END_DATE']))."'";
}

$search_text = '';
$search = $START_DATE.$END_DATE. ' ';
if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = $START_DATE.$END_DATE." AND (DOA_ENROLLMENT_MASTER.ENROLLMENT_ID LIKE '%".$search_text."%' OR CUSTOMER.FIRST_NAME LIKE '%".$search_text."%' OR SERVICE_PROVIDER.FIRST_NAME LIKE '%".$search_text."%' OR CUSTOMER.LAST_NAME LIKE '%".$search_text."%' OR SERVICE_PROVIDER.LAST_NAME LIKE '%".$search_text."%' OR CUSTOMER.EMAIL_ID LIKE '%".$search_text."%' OR CUSTOMER.PHONE LIKE '%".$search_text."%')";

}

if (isset($_GET['master_id']) && $_GET['master_id'] != '') {
    $PK_USER_MASTER = $_GET['master_id'];
    $query = $db_account->Execute("SELECT DISTINCT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER), count(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS TOTAL_RECORDS FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN $master_database.DOA_USERS AS CUSTOMER ON $master_database.DOA_USER_MASTER.PK_USER = $master_database.CUSTOMER.PK_USER LEFT JOIN $master_database.DOA_USER_LOCATION ON $master_database.CUSTOMER.PK_USER = $master_database.DOA_USER_LOCATION.PK_USER LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON $account_database.DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = $master_database.SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND DOA_APPOINTMENT_MASTER.CUSTOMER_ID='$PK_USER_MASTER' AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search);
} else {
    $query = $db_account->Execute("SELECT DISTINCT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER), count(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS TOTAL_RECORDS FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN $master_database.DOA_USERS AS CUSTOMER ON $master_database.DOA_USER_MASTER.PK_USER = $master_database.CUSTOMER.PK_USER LEFT JOIN $master_database.DOA_USER_LOCATION ON $master_database.CUSTOMER.PK_USER = $master_database.DOA_USER_LOCATION.PK_USER LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON $account_database.DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = $master_database.SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search);
}

$number_of_result =  $query->fields['TOTAL_RECORDS'];
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
                    <button type="button" id="group_class" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=group_class'"><i class="fa fa-plus-circle"></i> Group Class</button>
                    <button type="button" id="int_app" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=int_app'"><i class="fa fa-plus-circle"></i> INT APP</button>
                    <button type="button" id="appointment" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=appointment'"><i class="fa fa-plus-circle"></i> Appointment</button>
                    <button type="button" id="standing" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=standing'"><i class="fa fa-plus-circle"></i> Standing</button>
                    <button type="button" id="ad_hoc" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=ad_hoc'"><i class="fa fa-plus-circle"></i> Ad-hoc Appointment</button>
                    <button type="button" id="operations" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='operations.php'"><i class="ti-layers-alt"></i> <?=$operation_tab_title?></button>
                </div>
            </div>

            <div class="row page-titles">
                <div class="col-md-6 align-self-center">
                    <h4 class="text-themecolor">List</h4>
                </div>
                <div class="col-6">
                    <form class="form-material form-horizontal" action="" method="get">
                        <div class="input-group">
                            <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" placeholder="Start Date" value="<?=!empty($_GET['START_DATE'])?$_GET['START_DATE']:''?>">&nbsp;&nbsp;&nbsp;&nbsp;
                            <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" placeholder="End Date" value="<?=!empty($_GET['END_DATE'])?$_GET['END_DATE']:''?>">&nbsp;&nbsp;&nbsp;&nbsp;
                            <input class="form-control" type="text" id="search_text" name="search_text" placeholder="Search.." value="<?=$search_text?>">
                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white input-group-btn m-b-1" style="margin-bottom: 1px" onsubmit="showListView(1)"><i class="fa fa-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped border" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th data-type="number" class="sortable" style="cursor: pointer">No</th>
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
                                    if (isset($_GET['master_id']) && $_GET['master_id'] != '') {
                                        $PK_USER_MASTER = $_GET['master_id'];
                                        $appointment_data = $db_account->Execute("SELECT DISTINCT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS, DOA_APPOINTMENT_MASTER.IS_PAID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, CONCAT($master_database.CUSTOMER.FIRST_NAME, ' ', $master_database.CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT($master_database.SERVICE_PROVIDER.FIRST_NAME, ' ', $master_database.SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN $master_database.DOA_USERS AS CUSTOMER ON $master_database.DOA_USER_MASTER.PK_USER = $master_database.CUSTOMER.PK_USER LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = $master_database.SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND DOA_APPOINTMENT_MASTER.DATE < '".date('Y-m-d')."' AND DOA_APPOINTMENT_MASTER.IS_PAID = 0 AND DOA_APPOINTMENT_MASTER.CUSTOMER_ID='$PK_USER_MASTER' AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search." ORDER BY DATE DESC LIMIT " . $page_first_result . ',' . $results_per_page);
                                    } else {
                                        $appointment_data = $db_account->Execute("SELECT DISTINCT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS, DOA_APPOINTMENT_MASTER.IS_PAID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, CONCAT($master_database.CUSTOMER.FIRST_NAME, ' ', $master_database.CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT($master_database.SERVICE_PROVIDER.FIRST_NAME, ' ', $master_database.SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN $master_database.DOA_USERS AS CUSTOMER ON $master_database.DOA_USER_MASTER.PK_USER = $master_database.CUSTOMER.PK_USER LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = $master_database.SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND DOA_APPOINTMENT_MASTER.DATE < '".date('Y-m-d')."' AND DOA_APPOINTMENT_MASTER.IS_PAID = 0 AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search." ORDER BY DATE DESC LIMIT " . $page_first_result . ',' . $results_per_page);
                                    }

                                    while (!$appointment_data->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['CUSTOMER_NAME']?></td>
                                            <? if (!empty($appointment_data->fields['ENROLLMENT_ID'])) { ?>
                                                <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['ENROLLMENT_ID']." || ".$appointment_data->fields['SERVICE_NAME']." || ".$appointment_data->fields['SERVICE_CODE']?></td>
                                            <? } elseif (empty($appointment_data->fields['SERVICE_NAME']) && empty($appointment_data->fields['SERVICE_CODE'])) { ?>
                                                <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_NAME']."  ".$appointment_data->fields['SERVICE_CODE']?></td>
                                            <? } else {?>
                                                <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_NAME']." || ".$appointment_data->fields['SERVICE_CODE']?></td>
                                            <? }?>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_PROVIDER_NAME']?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('l', strtotime($appointment_data->fields['DATE']))?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('m/d/Y', strtotime($appointment_data->fields['DATE']))?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=($appointment_data->fields['IS_PAID'] == 0)?'Unpaid':'Paid'?></td>
                                            <td style="text-align: center;">
                                                <?php if ($appointment_data->fields['PK_APPOINTMENT_STATUS'] == 2){ ?>
                                                    <i class="fa fa-check-circle" style="font-size:25px;color:#35e235;"></i>
                                                <?php } else { ?>
                                                    <a href="all_schedules.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>&action=complete" onclick='javascript:confirmComplete($(this));return false;'><i class="fa fa-check-circle" style="font-size:25px;color:#a9b7a9;"></i></a>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <?php if(empty($appointment_data->fields['ENROLLMENT_ID'])) { ?>
                                                    <a href="create_appointment.php?type=ad_hoc&id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php } else { ?>
                                                    <a href="add_schedule.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php } ?>
                                                <a href="copy_schedule.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>"><i class="fa fa-copy"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <a href="all_schedules.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>" onclick='javascript:ConfirmDelete(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);return false;'><img src="../assets/images/delete.png" title="Delete"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                            </td>
                                        </tr>
                                        <?php $appointment_data->MoveNext();
                                        $i++; } ?>
                                    </tbody>
                                </table>

                                <div class="center">
                                    <div class="pagination outer">
                                        <ul>
                                            <?php if ($page > 1) { ?>
                                                <li><a href="appointment_list.php?status=&page=1">&laquo;</a></li>
                                                <li><a href="appointment_list.php?status=&page=<?=($page-1)?>">&lsaquo;</a></li>
                                            <?php }
                                            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                                                if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                                                    echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="appointment_list.php?status=&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                } elseif ($page_count == ($number_of_page-1)){
                                                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                } else {
                                                    echo '<li><a class="hidden" href="appointment_list.php?status=&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                }
                                            }
                                            if ($page < $number_of_page) { ?>
                                                <li><a href="appointment_list.php?status=&page=<?=($page+1)?>">&rsaquo;</a></li>
                                                <li><a href="appointment_list.php?status=&page=<?=$number_of_page?>">&raquo;</a></li>
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
    function ConfirmDelete(PK_APPOINTMENT_MASTER)
    {
        var conf = confirm("Are you sure you want to delete this appointment?");
        if(conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {FUNCTION_NAME: 'deleteAppointment', PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER},
                success: function (data) {
                    window.location.href = 'customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER+'&tab=appointment';
                }
            });
        }
    }
</script>
