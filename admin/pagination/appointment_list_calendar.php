<?php
require_once('../../global/config.php');

$results_per_page = 100;

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
    $search = $START_DATE.$END_DATE." AND (DOA_ENROLLMENT_MASTER.ENROLLMENT_ID LIKE '%".$search_text."%' OR CUSTOMER.FIRST_NAME LIKE '%".$search_text."%' OR SERVICE_PROVIDER.FIRST_NAME LIKE '%".$search_text."%')";

}

if (isset($_GET['master_id']) && $_GET['master_id'] != '') {
    $PK_USER_MASTER = $_GET['master_id'];
    $query = $db_account->Execute("SELECT DISTINCT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER), count(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS TOTAL_RECORDS FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN $master_database.DOA_USERS AS CUSTOMER ON $master_database.DOA_USER_MASTER.PK_USER = $master_database.CUSTOMER.PK_USER LEFT JOIN $master_database.DOA_USER_LOCATION ON $master_database.CUSTOMER.PK_USER = $master_database.DOA_USER_LOCATION.PK_USER LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON $account_database.DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = $master_database.SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS IN (1, 3, 5, 7, 8) AND DOA_APPOINTMENT_MASTER.CUSTOMER_ID='$PK_USER_MASTER' AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search);
} else {
    $query = $db_account->Execute("SELECT DISTINCT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER), count(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS TOTAL_RECORDS FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN $master_database.DOA_USERS AS CUSTOMER ON $master_database.DOA_USER_MASTER.PK_USER = $master_database.CUSTOMER.PK_USER LEFT JOIN $master_database.DOA_USER_LOCATION ON $master_database.CUSTOMER.PK_USER = $master_database.DOA_USER_LOCATION.PK_USER LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON $account_database.DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = $master_database.SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS IN (1, 3, 5, 7, 8) AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search);
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
<table id="myTable" class="table table-striped border" data-page-length='50'>
    <thead>
    <tr>
        <th data-type="number">No</th>
        <th data-type="string">Customer</th>
        <th data-type="string">Enrollment ID</th>
        <th data-type="string"><?=$service_provider_title?></th>
        <th data-type="string">Day-Date-Time</th>
        <!--<th data-date data-order>Date</th>
        <th data-type="string">Time</th>-->
        <th data-type="string">Paid</th>
        <th data-type="string" style="text-align: center;">Completed</th>
        <th data-type="string" style="width: 15%">Actions</th>
    </tr>
    </thead>

    <tbody >
    <?php
    $i=$page_first_result+1;
    if (isset($_GET['master_id']) && $_GET['master_id'] != '') {
        $PK_USER_MASTER = $_GET['master_id'];
        $appointment_data = $db_account->Execute("SELECT DISTINCT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS, DOA_APPOINTMENT_MASTER.IS_PAID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, CONCAT($master_database.CUSTOMER.FIRST_NAME, ' ', $master_database.CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT($master_database.SERVICE_PROVIDER.FIRST_NAME, ' ', $master_database.SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN $master_database.DOA_USERS AS CUSTOMER ON $master_database.DOA_USER_MASTER.PK_USER = $master_database.CUSTOMER.PK_USER LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = $master_database.SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS IN (1, 3, 5, 7, 8) AND DOA_APPOINTMENT_MASTER.IS_PAID = 0 AND DOA_APPOINTMENT_MASTER.CUSTOMER_ID='$PK_USER_MASTER' AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search." ORDER BY DATE DESC LIMIT " . $page_first_result . ',' . $results_per_page);
    } else {
        $appointment_data = $db_account->Execute("SELECT DISTINCT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS, DOA_APPOINTMENT_MASTER.IS_PAID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, CONCAT($master_database.CUSTOMER.FIRST_NAME, ' ', $master_database.CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT($master_database.SERVICE_PROVIDER.FIRST_NAME, ' ', $master_database.SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN $master_database.DOA_USERS AS CUSTOMER ON $master_database.DOA_USER_MASTER.PK_USER = $master_database.CUSTOMER.PK_USER LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = $master_database.SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS IN (1, 3, 5, 7, 8) AND DOA_APPOINTMENT_MASTER.IS_PAID = 0 AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search." ORDER BY DATE DESC LIMIT " . $page_first_result . ',' . $results_per_page);
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
            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=substr(date('l', strtotime($appointment_data->fields['DATE'])), 0, 4).' - '.date('m/d/Y', strtotime($appointment_data->fields['DATE'])).' '.date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
            <!--<td onclick="editpage(<?php /*=$appointment_data->fields['PK_APPOINTMENT_MASTER']*/?>);"><?php /*=date('m/d/Y', strtotime($appointment_data->fields['DATE']))*/?></td>
            <td onclick="editpage(<?php /*=$appointment_data->fields['PK_APPOINTMENT_MASTER']*/?>);"><?php /*=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))*/?></td>-->
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
                <a href="all_schedules.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>" onclick='javascript:ConfirmDelete(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);return false;'><img src="../assets/images/delete.png" title="Delete"></a>
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
    $(function() {
        const ths = $("th");
        let sortOrder = 1;

        ths.on("click", function() {
            const rows = sortRows(this);
            rebuildTbody(rows);
            updateClassName(this);
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

        function updateClassName(th) {
            let k;
            for (k=0; k<ths.length; k++) {
                ths[k].className = "";
            }
            th.className = sortOrder === 1 ? "asc" : "desc";
        }

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
    function ConfirmDelete(PK_APPOINTMENT_MASTER)
    {
        var conf = confirm("Are you sure you want to delete this appointment?");
        if(conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {FUNCTION_NAME: 'deleteAppointment', PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER},
                success: function (data) {
                    window.location.href = `all_schedules.php?view=table`;
                }
            });
        }
    }
</script>
