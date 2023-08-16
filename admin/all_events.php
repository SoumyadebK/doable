<?php
require_once('../global/config.php');
$title = "All Events";

$status_check = empty($_GET['status'])?'active':$_GET['status'];

if ($status_check == 'active'){
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$results_per_page = 100;

if (isset($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = " AND DOA_EVENT.HEADER LIKE '%".$search_text."%' OR DOA_EVENT.START_DATE LIKE '%".$search_text."%' OR DOA_EVENT.START_TIME LIKE '%".$search_text."%'";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db_account->Execute("SELECT count(DOA_EVENT.PK_EVENT) AS TOTAL_RECORDS FROM `DOA_EVENT` LEFT JOIN DOA_EVENT_TYPE ON DOA_EVENT.PK_EVENT_TYPE = DOA_EVENT_TYPE.PK_EVENT_TYPE WHERE DOA_EVENT.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_EVENT.PK_ACCOUNT_MASTER =".$_SESSION['PK_ACCOUNT_MASTER'].$search);
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
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    .pagination {
        display: inline-block;
    }
    .pagination a {
        color: black;
        float: left;
        padding: 8px 16px;
        text-decoration: none;
        transition: background-color .3s;
        border: 1px solid #ddd;
        margin: 0 4px;
    }
    .pagination a.active {
        background-color: #39B54A;
        color: white;
        border: 1px solid #39B54A;
    }
    .pagination a:hover:not(.active) {background-color: #ddd;}
</style>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content">
            <div class="row page-titles">
                <div class="col-md-2 align-self-center">
                    <?php if ($status_check=='inactive') { ?>
                        <h4 class="text-themecolor">Not Active Events</h4>
                    <?php } elseif ($status_check=='active') { ?>
                        <h4 class="text-themecolor">Active Events</h4>
                    <?php } ?>
                </div>

                <?php if ($status_check=='inactive') { ?>
                    <div class="col-md-3 align-self-center">
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_events.php?status=active'"><i class="fa fa-user"></i> Show Active</button>
                    </div>
                <?php } elseif ($status_check=='active') { ?>
                    <div class="col-md-3 align-self-center">
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_events.php?status=inactive'"><i class="fa fa-user-times"></i> Show Not Active</button>
                    </div>
                <?php } ?>

                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <button type="button" style="margin-right: 78%;" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='event.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body" style="height: 100px">
                    <div class="row">
                        <div class="col-2">
                            <div class="form-group">
                                <label class="form-label">Event Type</label>
                                <select class="form-control" name="PK_EVENT_TYPE" id="PK_EVENT_TYPE">
                                    <option value="">Select Event Type</option>
                                    <?php
                                    $row = $db_account ->Execute("SELECT * FROM `DOA_EVENT_TYPE` WHERE PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." AND `ACTIVE` = 1");
                                    while (!$row->EOF) {?>
                                        <option value="<?php echo $row->fields['EVENT_TYPE'];?>"><?=$row->fields['EVENT_TYPE']?></option>
                                        <?php $row->MoveNext(); } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-2">
                            <div class="form-group">
                                <label class="form-label">Event Status</label>
                                <select class="form-control" name="PK_EVENT_STATUS" id="PK_EVENT_STATUS">
                                    <option value="">Select Event Status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-2">
                            <div class="form-group">
                                <label class="form-label">From Date</label>
                                <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" value="">
                            </div>
                        </div>
                        <div class="col-2">
                            <div class="form-group">
                                <label class="form-label">To Date</label>
                                <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" required value="">
                            </div>
                        </div>
                        <div class="col-4" >
                            <form class="form-material form-horizontal" action="" method="get">
                                <label class="form-label">Search</label>
                                <div class="input-group" style="margin-top: 6px">
                                    <input class="form-control" type="text" name="search_text" placeholder="Search.." value="<?=$search_text?>">
                                    <button class="btn btn-info waves-effect waves-light m-r-10 text-white input-group-btn m-b-1" type="submit"><i class="fa fa-search"></i></button>
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
                            <div class="table-responsive">
                                <table class="table table-striped border" data-page-length='50'>
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Event Name</th>
                                            <th>Type</th>
                                            <th>Location</th>
                                            <th>Start Date</th>
                                            <th>Start Time</th>
                                            <th>End Date</th>
                                            <th>End Time</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=$page_first_result+1;
                                    $row = $db->Execute("SELECT $account_database.DOA_EVENT.*, $account_database.DOA_EVENT_TYPE.EVENT_TYPE, $master_database.DOA_LOCATION.LOCATION_NAME FROM $account_database.`DOA_EVENT` LEFT JOIN $account_database.DOA_EVENT_TYPE ON $account_database.DOA_EVENT.PK_EVENT_TYPE = $account_database.DOA_EVENT_TYPE.PK_EVENT_TYPE LEFT JOIN $master_database.DOA_LOCATION ON $account_database.DOA_EVENT.PK_LOCATION = $master_database.DOA_LOCATION.PK_LOCATION WHERE $account_database.DOA_EVENT.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND $account_database.DOA_EVENT.ACTIVE='$status' AND $account_database.DOA_EVENT.PK_ACCOUNT_MASTER =".$_SESSION['PK_ACCOUNT_MASTER'].$search." ORDER BY $account_database.DOA_EVENT.START_DATE DESC LIMIT " . $page_first_result . ',' . $results_per_page);
                                    while (!$row->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_EVENT']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_EVENT']?>);"><?=$row->fields['HEADER']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_EVENT']?>);"><?=$row->fields['EVENT_TYPE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_EVENT']?>);"><?=$row->fields['LOCATION_NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_EVENT']?>);"><?=date('m/d/Y',strtotime($row->fields['START_DATE']))?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_EVENT']?>);"><?=date('h:i A', strtotime($row->fields['START_TIME']))?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_EVENT']?>);"><?=($row->fields['END_DATE'] == '0000-00-00')?'':date('m/d/Y',strtotime($row->fields['END_DATE']))?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_EVENT']?>);"><?=($row->fields['END_TIME'] == '00:00:00')?'':date('h:i A', strtotime($row->fields['END_TIME']))?></td>
                                            <td>
                                                <a href="event.php?id=<?=$row->fields['PK_EVENT']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php if($row->fields['ACTIVE']==1){ ?>
                                                    <span class="active-box-green"></span>
                                                    <span class="d-none">1</span>
                                                <?php } else{ ?>
                                                    <span class="active-box-red"></span>
                                                    <span class="d-none">0</span>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php $row->MoveNext();
                                        $i++; } ?>
                                    </tbody>
                                </table>
                                <div class="center">
                                    <div class="pagination outer">
                                        <ul>
                                            <?php if ($page > 1) { ?>
                                                <li><a href="all_events.php?page=1">&laquo;</a></li>
                                                <li><a href="all_events.php?page=<?=($page-1)?>">&lsaquo;</a></li>
                                            <?php }
                                            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                                                if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                                                    echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="all_events.php?page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                } elseif ($page_count == ($number_of_page-1)){
                                                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                } else {
                                                    echo '<li><a class="hidden" href="all_events.php?page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                }
                                            }
                                            if ($page < $number_of_page) { ?>
                                                <li><a href="all_events.php?page=<?=($page+1)?>">&rsaquo;</a></li>
                                                <li><a href="all_events.php?page=<?=$number_of_page?>">&raquo;</a></li>
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
<script src="https://cdn.datatables.net/plug-ins/1.10.11/sorting/date-eu.js"></script>

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

        $("#myTable").dataTable({
            "searching": true,
            "columnDefs" : [{"targets":[3,5], "type":"date-eu"}],
        });

        var table = $('#myTable').DataTable();

        $("#filterTable_filter.dataTables_filter").append($("#PK_EVENT_TYPE"));
        var typeIndex = 2;
        var statusIndex = 7;

        /*var startDateIndex = 3;
        var endDateIndex = 5;*/

        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {

              var eventType   = $('#PK_EVENT_TYPE').val();
              var eventStatus = $('#PK_EVENT_STATUS').val();

              var eventTypeVal  = data[typeIndex];
              var eventStatusVal  = data[statusIndex];

              var startDate = $('#START_DATE').val();
              var endDate = $('#END_DATE').val();

              var startedAt = data[3] || 0;
              var endedAt = data[5] || 0;

              /*var startDateVal = data[startDateIndex];
              var endDateVal = data[endDateIndex];*/
              if (
                    (eventType === "" || eventTypeVal.includes(eventType)) 
                    && (eventStatus === "" || eventStatusVal.includes(eventStatus))
                    && (startDate == "" || moment(startedAt).isSameOrAfter(startDate))
                    && (endDate == "" || moment(endedAt).isSameOrBefore(endDate))
                  ) 
              {
                return true;
              }
              return false;
            }
        );

        $("#PK_EVENT_TYPE, #PK_EVENT_STATUS, #START_DATE").change(function (e) {
            table.draw();
        });

        $('#START_DATE, #END_DATE').on('input', function (e) {
            table.draw();
        });

        table.draw();

    });
    function ConfirmDelete(anchor)
    {
        let conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=anchor.attr("href");
    }
    function editpage(id){
        window.location.href = "event.php?id="+id;
    }
</script>
</body>
</html>