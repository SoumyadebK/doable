<?php
require_once('../global/config.php');
$title = "All Events";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <a href="all_event_types.php" style="margin-right: 20px; color: #39B54A;">Event Type</a>
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='event.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-3">
                    <div class="form-group">
                        <label class="form-label">Event Type</label>
                        <select class="form-control" name="PK_EVENT_TYPE" id="PK_EVENT_TYPE">
                            <option value="">Select Event Type</option>
                            <?php
                            $row = $db->Execute("SELECT * FROM `DOA_EVENT_TYPE` WHERE PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." AND `ACTIVE` = 1");
                            while (!$row->EOF) {?>
                                <option value="<?php echo $row->fields['EVENT_TYPE'];?>"><?=$row->fields['EVENT_TYPE']?></option>
                            <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
                <div class="col-3">
                    <div class="form-group">
                        <label class="form-label">Event Status</label>
                        <select class="form-control" name="PK_EVENT_STATUS" id="PK_EVENT_STATUS">
                            <option value="">Select Event Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="col-3">
                    <div class="form-group">
                        <label class="form-label">From Date</label>
                        <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" value="">
                    </div>
                </div>
                <div class="col-3">
                    <div class="form-group">
                        <label class="form-label">To Date</label>
                        <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" required value="">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="myTable" class="table table-striped border">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Event Name</th>
                                            <th>Type</th>
                                            <th>Start Date</th>
                                            <th>Start Time</th>
                                            <th>End Date</th>
                                            <th>End Time</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db->Execute("SELECT DOA_EVENT.*, DOA_EVENT_TYPE.EVENT_TYPE FROM `DOA_EVENT` LEFT JOIN DOA_EVENT_TYPE ON DOA_EVENT.PK_EVENT_TYPE = DOA_EVENT_TYPE.PK_EVENT_TYPE WHERE DOA_EVENT.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                    while (!$row->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_EVENT']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_EVENT']?>);"><?=$row->fields['HEADER']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_EVENT']?>);"><?=$row->fields['EVENT_TYPE']?></td>
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
            "searching": true
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