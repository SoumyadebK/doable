<?php
require_once('../global/config.php');
$title = "Appointments";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 5 ){
    header("location:../login.php");
    exit;
}

if (!empty($_GET['id']) && !empty($_GET['action'])){
    if ($_GET['action'] == 'complete'){
        $db->Execute("UPDATE DOA_APPOINTMENT_MASTER SET PK_APPOINTMENT_STATUS = 2 WHERE PK_APPOINTMENT_MASTER = ".$_GET['id']);
        header("location:operations.php?view=list");
    }
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

$location_data = $db->Execute("SELECT * FROM DOA_USER_LOCATION WHERE PK_USER = '$_SESSION[PK_USER]'");
$LOCATION_ARRAY = [];
if ($location_data->RecordCount() > 0) {
    while (!$location_data->EOF) {
        $LOCATION_ARRAY[] = $location_data->fields['PK_LOCATION'];
        $location_data->MoveNext();
    }
}

$SERVICE_PROVIDER_ARRAY[] = $_SESSION['PK_USER'];
$location_data = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER) FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USERS.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (".implode(',', $LOCATION_ARRAY).")");
if ($location_data->RecordCount() > 0) {
    $SERVICE_PROVIDER_ARRAY = [];
    while (!$location_data->EOF) {
        $SERVICE_PROVIDER_ARRAY[] = $location_data->fields['PK_USER'];
        $location_data->MoveNext();
    }
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
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Service Provider</label>
                        <select class="form-control" name="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID">
                            <option value="">Select <?=$service_provider_title?></option>
                            <?php
                            $selected_service_provider = '';
                            $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_ID, DOA_ROLES.ROLES, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE, DOA_USERS.USER_TITLE FROM DOA_USERS LEFT JOIN DOA_ROLES ON DOA_ROLES.PK_ROLES = DOA_USERS.PK_ROLES WHERE DOA_USERS.PK_ROLES = 5 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                            while (!$row->EOF) { ?>
                                <option value="<?=$row->fields['NAME']?>"><?=$row->fields['NAME']?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">From Date</label>
                        <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal">
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">To Date</label>
                        <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div style="padding-left:12px; padding-top: 20px"><button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="markAllComplete()"><i class="ti-check-box"></i> Completed</button></div>
                            <tr id="list"  class="card-body">
                                <table id="myTable" class="table table-striped border" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th><input type="checkbox" onClick="toggle(this)" /></th>
                                        <th>Customer</th>
                                        <th>Enrollment ID</th>
                                        <th>Service</th>
                                        <th>Service Code</th>
                                        <th><?=$service_provider_title?></th>
                                        <th>Day</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Paid</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $appointment_data = $db->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.IS_PAID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER LEFT JOIN DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID IN (".implode(',', $SERVICE_PROVIDER_ARRAY).") ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC");
                                    while (!$appointment_data->EOF) { ?>
                                        <tr>
                                            <td <label><input type="checkbox" name="PK_APPOINTMENT_MASTER[]" class="PK_APPOINTMENT_MASTER" value="<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>"></label></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['CUSTOMER_NAME']?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['ENROLLMENT_ID']?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_NAME']?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_CODE']?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_PROVIDER_NAME']?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('l', strtotime($appointment_data->fields['DATE']))?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('m/d/Y', strtotime($appointment_data->fields['DATE']))?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=($appointment_data->fields['IS_PAID'] == 0)?'Unpaid':'Paid'?></td>
                                            <td>
                                                <a href="add_schedule.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                            </td>
                                        </tr>
                                        <?php $appointment_data->MoveNext();
                                        $i++; } ?>
                                    </tbody>
                                </table>

                                <!--                                <div style="padding-left:41px; padding-top: 20px"><input type="checkbox" onClick="toggle(this)" />  Mark all</div>-->
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

    $(document).ready(function(){
        $("#START_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(selected) {
                $("#END_DATE").datepicker("option","minDate", selected);
                $(this).change();
            }
        });
        $("#END_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(selected) {
                $("#START_DATE").datepicker("option","maxDate", selected);
                $(this).change();
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

    function ConfirmDelete(anchor)
    {
        let conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=anchor.attr("href");
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
</body>
</html>