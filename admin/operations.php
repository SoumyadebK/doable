<?php
require_once('../global/config.php');
$title = "All Appointments";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if(empty($_GET['id'])){
    $SERVICE_PROVIDER_ID = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_APPOINTMENT_MASTER` = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_schedules.php?view=list");
        exit;
    }

    $SERVICE_PROVIDER_ID = $res->fields['SERVICE_PROVIDER_ID'];
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
            </div>

            <div class="row">
                <div class="col-3">
                    <div class="form-group">
                        <label class="form-label">Service Provider</label>
                        <select class="form-control" name="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID">
                            <option value="">Select <?=$service_provider_title?></option>
                            <?php
                            $selected_service_provider = '';
                            $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_ID, DOA_ROLES.ROLES, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE, DOA_USERS.USER_TITLE FROM DOA_USERS LEFT JOIN DOA_ROLES ON DOA_ROLES.PK_ROLES = DOA_USERS.PK_ROLES WHERE DOA_USERS.PK_ROLES = 5 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                            while (!$row->EOF) { if($SERVICE_PROVIDER_ID==$row->fields['PK_USER']){$selected_service_provider = $row->fields['NAME'];} ?>
                                <option value="<?php echo $row->fields['PK_USER'];?>" <?=($SERVICE_PROVIDER_ID==$row->fields['PK_USER'])?'selected':''?>><?=$row->fields['NAME']?></option>
                                <?php $row->MoveNext(); } ?>
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
                            <div id="list"  class="card-body">
                                <table id="myTable" class="table table-striped border">
                                    <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Customer</th>
                                        <th>Enrollment ID</th>
                                        <th>Service</th>
                                        <th>Service Code</th>
                                        <th><?=$service_provider_title?></th>
                                        <th>Day</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Paid</th>
                                        <th style="text-align: center;">Completed</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    if ($DEFAULT_LOCATION_ID > 0){
                                        $appointment_data = $db->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS, DOA_APPOINTMENT_MASTER.IS_PAID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER LEFT JOIN DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = SERVICE_PROVIDER.PK_USER INNER JOIN DOA_USER_LOCATION ON SERVICE_PROVIDER.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_USER_LOCATION.PK_LOCATION = '$DEFAULT_LOCATION_ID' AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC");
                                    } else {
                                        $appointment_data = $db->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS, DOA_APPOINTMENT_MASTER.IS_PAID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER LEFT JOIN DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC");
                                    }
                                    while (!$appointment_data->EOF) { ?>
                                        <tr>
                                            <td <label><input type="checkbox" name="PK_APPOINTMENT_MASTER[]" value="<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>"></label> <!--onclick="editpage(<?/*=$appointment_data->fields['PK_APPOINTMENT_MASTER']*/?>);">--><?/*=$i;*/?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['CUSTOMER_NAME']?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['ENROLLMENT_ID']?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_NAME']?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_CODE']?></td>
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
                                                <a href="add_schedule.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php /*if($appointment_data->fields['ACTIVE']==1){ */?><!--
                                                    <span class="active-box-green"></span>
                                                <?php /*} else{ */?>
                                                    <span class="active-box-red"></span>
                                                --><?php /*} */?>
                                            </td>
                                        </tr>
                                        <?php $appointment_data->MoveNext();
                                        $i++; } ?>
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='event.php'" ><i class="ti-check-box"></i> Completed</button>
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

        $("#filterTable_filter.dataTables_filter").append($("#SERVICE_PROVIDER_ID"));
        var typeIndex = 2;
        var statusIndex = 7;

        /*var startDateIndex = 3;
        var endDateIndex = 5;*/

        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {

                var eventType   = $('#SERVICE_PROVIDER_ID').val();
                //var eventStatus = $('#PK_EVENT_STATUS').val();

                var eventTypeVal  = data[typeIndex];
                //var eventStatusVal  = data[statusIndex];

                var startDate = $('#START_DATE').val();
                var endDate = $('#END_DATE').val();

                var startedAt = data[7] || 0;
                var endedAt = data[7] || 0;

                /*var startDateVal = data[startDateIndex];
                var endDateVal = data[endDateIndex];*/
                if (
                    (eventType === "" || eventTypeVal.includes(eventType))
                    && (startDate == "" || moment(startedAt).isSameOrAfter(startDate))
                    && (endDate == "" || moment(endedAt).isSameOrBefore(endDate))
                )
                {
                    return true;
                }
                return false;
            }
        );

        $("#SERVICE_PROVIDER_ID, #START_DATE, #END_DATE").change(function (e) {
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
        window.location.href = "add_schedule.php?id="+id;
    }
</script>
</body>
</html>