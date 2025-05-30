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
$search = $START_DATE.$END_DATE. ' ';
if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = $START_DATE.$END_DATE." AND (DOA_SERVICE_MASTER.SERVICE_NAME LIKE '%".$search_text."%' OR DOA_SERVICE_CODE.SERVICE_CODE LIKE '%".$search_text."%' OR CUSTOMER.FIRST_NAME LIKE '%".$search_text."%' OR SERVICE_PROVIDER.FIRST_NAME LIKE '%".$search_text."%')";
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
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;
?>

<table id="myTable" class="table table-striped border" data-page-length='50'>
    <thead>
        <tr>
            <th data-type="number" class="sortable" style="cursor: pointer">No</i></th>
            <th data-type="string" class="sortable" style="cursor: pointer">Customer</th>
            <th data-type="string" class="sortable" style="cursor: pointer">Enrollment ID</th>
            <th data-type="string" class="sortable" style="text-align: left;">Apt #</th>
            <th data-type="number" class="sortable" style="cursor: pointer">Serial No</i></th>
            <th data-type="string" class="sortable" style="cursor: pointer"><?=$service_provider_title?></th>
            <th data-type="string" class="sortable" style="cursor: pointer">Day</th>
            <th data-date data-order class="sortable" style="cursor: pointer">Date</th>
            <th data-type="string" class="sortable" style="cursor: pointer">Time</th>
            <th data-type="string" class="sortable" style="cursor: pointer">Comment & Uploads</th>
            <th>Paid</th>
            <th>Completed</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody id="apt_tbody">
        <?php
        $service_code_array = [];
        $i=$page_first_result+1;
        $appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY, $page_first_result . ',' . $results_per_page);
        while (!$appointment_data->EOF) {
            $status_data = $db_account->Execute("SELECT DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_APPOINTMENT_STATUS_HISTORY.TIME_STAMP FROM DOA_APPOINTMENT_STATUS_HISTORY LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS=DOA_APPOINTMENT_STATUS_HISTORY.PK_APPOINTMENT_STATUS LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER=DOA_APPOINTMENT_STATUS_HISTORY.PK_USER WHERE PK_APPOINTMENT_MASTER = ".$appointment_data->fields['PK_APPOINTMENT_MASTER']);
            $CHANGED_BY = '';
            while (!$status_data->EOF) {
                $CHANGED_BY .= "(".$status_data->fields['APPOINTMENT_STATUS']." by ".$status_data->fields['NAME']." at ".date('m-d-Y H:i:s A', strtotime($status_data->fields['TIME_STAMP'])).")<br>";
                $status_data->MoveNext();
            }
            $IMAGE_LINK = $appointment_data->fields['IMAGE'];
            $VIDEO_LINK = $appointment_data->fields['VIDEO'];
            if ($appointment_data->fields['APPOINTMENT_TYPE'] === 'NORMAL') {
                $SESSION_CREATED = getSessionCreatedCount($appointment_data->fields['PK_ENROLLMENT_SERVICE'], $appointment_data->fields['APPOINTMENT_TYPE']);
                $PK_ENROLLMENT_SERVICE = $appointment_data->fields['PK_ENROLLMENT_SERVICE'];
                $ENROLLMENT_ID = $appointment_data->fields['ENROLLMENT_ID'];
                $ENROLLMENT_NAME = $appointment_data->fields['ENROLLMENT_NAME'];
            } else {
                $SESSION_CREATED = getSessionCreatedCount($appointment_data->fields['APT_ENR_SERVICE'], $appointment_data->fields['APPOINTMENT_TYPE']);
                $PK_ENROLLMENT_SERVICE = $appointment_data->fields['APT_ENR_SERVICE'];
                $ENROLLMENT_ID = $appointment_data->fields['APT_ENR_NAME'];
                $ENROLLMENT_NAME = $appointment_data->fields['APT_ENR_ID'];
            }

            $enr_service_data = $db_account->Execute("SELECT NUMBER_OF_SESSION, SESSION_CREATED, SESSION_COMPLETED FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_SERVICE` = ".$PK_ENROLLMENT_SERVICE);
            if ($enr_service_data->RecordCount() > 0) {
                if (isset($service_code_array[$PK_ENROLLMENT_SERVICE])) {
                    $service_code_array[$PK_ENROLLMENT_SERVICE] = $service_code_array[$PK_ENROLLMENT_SERVICE] - 1;
                } else {
                    $service_code_array[$PK_ENROLLMENT_SERVICE] = $SESSION_CREATED;
                }
            } ?>
        <tr onclick="$(this).next().slideToggle();">
            <td><?=$i;?></td>
            <td><?=$appointment_data->fields['CUSTOMER_NAME']?></td>
            <?php if (!empty($ENROLLMENT_ID) || !empty($ENROLLMENT_NAME)) { ?>
                <td><?=(($ENROLLMENT_NAME) ? $ENROLLMENT_NAME.' - ' : '').$ENROLLMENT_ID." || ".$appointment_data->fields['SERVICE_NAME']." || ".$appointment_data->fields['SERVICE_CODE']?></td>
            <?php } elseif (empty($appointment_data->fields['SERVICE_NAME']) && empty($appointment_data->fields['SERVICE_CODE'])) { ?>
                <td><?=$appointment_data->fields['SERVICE_NAME']."  ".$appointment_data->fields['SERVICE_CODE']?></td>
            <?php } else { ?>
                <td><?=$appointment_data->fields['SERVICE_NAME']." || ".$appointment_data->fields['SERVICE_CODE']?></td>
            <?php } ?>
            <td><?=(isset($service_code_array[$PK_ENROLLMENT_SERVICE])) ? $service_code_array[$PK_ENROLLMENT_SERVICE].'/'.$enr_service_data->fields['NUMBER_OF_SESSION'] : ''?></td>
            <td><?=$appointment_data->fields['SERIAL_NUMBER']?></td>
            <td><?=$appointment_data->fields['SERVICE_PROVIDER_NAME']?></td>
            <td><?=date('l', strtotime($appointment_data->fields['DATE']))?></td>
            <td><?=date('m/d/Y', strtotime($appointment_data->fields['DATE']))?></td>
            <td><?=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
            <td style="cursor: pointer; vertical-align: middle; text-align: center;"><?php if($appointment_data->fields['COMMENT'] != '' || $IMAGE_LINK!='' || $VIDEO_LINK!='' || $CHANGED_BY!='') { ?>
                    <button class="btn btn-info waves-effect waves-light m-r-10 text-white">View</button> <?php } ?>
            </td>
            <td><?=($appointment_data->fields['IS_PAID'] == 1)?'Paid':'Unpaid'?></td>
            <td style="text-align: center;">
                <?php if ($appointment_data->fields['PK_APPOINTMENT_STATUS'] == 6 && $appointment_data->fields['IS_CHARGED'] == 1){ ?>
                    <i class="fa fa-check-circle" style="font-size:25px;color:red;"></i>
                <?php } elseif ($appointment_data->fields['PK_APPOINTMENT_STATUS'] == 2){ ?>
                    <i class="fa fa-check-circle" style="font-size:25px;color:#35e235;"></i>
                <?php } else { ?>
                    <a href="all_schedules.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>&action=complete" data-id="<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>" onclick='confirmComplete($(this));return false;'><i class="fa fa-check-circle" style="font-size:25px;color:#a9b7a9;"></i></a>
                <?php } ?>
            </td>
            <td>
                <?php /*if(empty($ENROLLMENT_ID)) { */?><!--
                        <a href="create_appointment.php?type=ad_hoc&id=<?php /*=$appointment_data->fields['PK_APPOINTMENT_MASTER']*/?>"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <?php /*} else { */?>
                        <a href="add_schedule.php?id=<?php /*=$appointment_data->fields['PK_APPOINTMENT_MASTER']*/?>"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <?php /*} */?>
                    <a href="copy_schedule.php?id=<?php /*=$appointment_data->fields['PK_APPOINTMENT_MASTER']*/?>"><i class="fa fa-copy"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-->
                    <a href="all_schedules.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>" onclick='ConfirmDelete(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);'><i class="fa fa-trash"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <?php if ($type == 'cancelled' && ($enr_service_data->RecordCount() > 0 && ($enr_service_data->fields['NUMBER_OF_SESSION']!=$SESSION_CREATED))) { ?>
                    <a href="all_schedules.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>" onclick='ConfirmScheduled(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>,<?=$PK_ENROLLMENT_SERVICE?>);' style="font-size: 18px"><i class="far fa-calendar-check"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <?php } ?>
            </td>
        </tr>
            <tr style="display: none">
                <td style="vertical-align: middle; text-align: center;" colspan="13">
                    <div class="col-12">
                        <div class="form-group">
                            <textarea class="form-control" name="COMMENT" rows="3"><?=$appointment_data->fields['COMMENT']?></textarea><span><?=$CHANGED_BY?></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <a href="<?=$IMAGE_LINK?>" target="_blank">
                                    <img src="<?=$IMAGE_LINK?>" style="margin-top: 15px; max-width: 150px; height: auto;">
                                </a>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <a href="<?=$VIDEO_LINK?>" target="_blank">
                                    <?php if($VIDEO_LINK != '') {?>
                                        <video width="240" height="135" controls>
                                            <source src="<?=$VIDEO_LINK?>" type="video/mp4">
                                        </video>
                                    <?php }?>
                                </a>
                            </div>
                        </div>
                    </div>


                    <?php /*=$appointment_data->fields['COMMENT']*/?><!--
                    <?php /*if ($IMAGE_LINK != '' && $IMAGE_LINK != null) { */?>
                        (<a href="<?php /*=$IMAGE_LINK*/?>" target="_blank">View Image</a>)
                    <?php /*} */?>
                    <?php /*if ($VIDEO_LINK != '' && $VIDEO_LINK != null) { */?>
                        (<a href="<?php /*=$VIDEO_LINK*/?>" target="_blank">View Video</a>)
                    <?php /*} */?>
                    <br><span><?php /*=$CHANGED_BY*/?></span>-->
                </td>
            </tr>
            <tr style="display: none">

            </tr>
        <?php $appointment_data->MoveNext();
        $i++; } ?>
    </tbody>
</table>

<div class="center">
    <div class="pagination outer">
        <ul>
            <?php if ($page > 1) { ?>
                <li><a href="javascript:;" onclick="showListView(1)">&laquo;</a></li>
                <li><a href="javascript:;" onclick="showListView(<?=($page-1)?>)">&lsaquo;</a></li>
            <?php }
            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                    echo '<li><a class="'.(($page_count==$page)?"active":"").'" href="javascript:;" onclick="showListView('.$page_count.')">' . $page_count . ' </a></li>';
                } elseif ($page_count == ($number_of_page-1)){
                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                } else {
                    echo '<li><a class="hidden" href="javascript:;" onclick="showListView('.$page_count.')">' . $page_count . ' </a></li>';
                }
            }
            if ($page < $number_of_page) { ?>
                <li><a href="javascript:;" onclick="showListView(<?=($page+1)?>)">&rsaquo;</a></li>
                <li><a href="javascript:;" onclick="showListView(<?=$number_of_page?>)">&raquo;</a></li>
            <?php } ?>
        </ul>
    </div>
</div>

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

    function ConfirmScheduled(PK_APPOINTMENT_MASTER, PK_ENROLLMENT_SERVICE)
    {
        var conf = confirm("Are you sure you want to Schedule this appointment?");
        if(conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {FUNCTION_NAME: 'scheduleAppointment', PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER, PK_ENROLLMENT_SERVICE: PK_ENROLLMENT_SERVICE},
                success: function (data) {
                    window.location.href = 'customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER+'&tab=appointment';
                }
            });
        }
    }
</script>
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
            const rows = $.makeArray($('#apt_tbody > tr'));
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
            const tbody = $("#apt_tbody");
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
            th.className = sortOrder === 1 ? "sortable asc" : "sortable desc";
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
