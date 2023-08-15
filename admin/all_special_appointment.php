<?php
require_once('../global/config.php');
$title = "All Special Appointment";

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
        <div class="container-fluid" style="margin-top: 67px">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='add_special_appointment.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="myTable" class="table table-striped border" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Title</th>
                                        <th>Date</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db_account->Execute("SELECT DOA_SPECIAL_APPOINTMENT.*, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM `DOA_SPECIAL_APPOINTMENT` LEFT JOIN DOA_APPOINTMENT_STATUS ON DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                    while (!$row->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_SPECIAL_APPOINTMENT']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_SPECIAL_APPOINTMENT']?>);"><?=$row->fields['TITLE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_SPECIAL_APPOINTMENT']?>);"><?=date('m/d/Y',strtotime($row->fields['DATE']))?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_SPECIAL_APPOINTMENT']?>);"><?=date('h:i A', strtotime($row->fields['START_TIME']))?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_SPECIAL_APPOINTMENT']?>);"><?=date('h:i A', strtotime($row->fields['END_TIME']))?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_SPECIAL_APPOINTMENT']?>);"><span class="status-box" style="background-color: <?=$row->fields['COLOR_CODE']?>"><?=$row->fields['APPOINTMENT_STATUS']?></span></td>
                                            <td>
                                                <a href="../backup/add_special_appointment.php?id=<?=$row->fields['PK_SPECIAL_APPOINTMENT']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
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
<script>
    $(function () {
        $('#myTable').DataTable();
    });
    function ConfirmDelete(anchor)
    {
        let conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=anchor.attr("href");
    }
    function editpage(id){
        window.location.href = "add_special_appointment.php?id="+id;
    }
</script>
</body>
</html>