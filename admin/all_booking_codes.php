<?php
require_once('../global/config.php');
$title = "All Booking Codes";

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
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='add_booking_codes.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
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
                                        <th>Booking code</th>
                                        <th>Booking Name</th>
                                        <th>Booking Event</th>
                                        <th>Event Action</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db->Execute("SELECT DOA_BOOKING_CODES.PK_BOOKING_CODES, DOA_BOOKING_CODES.BOOKING_CODE, DOA_BOOKING_CODES.BOOKING_NAME, DOA_BOOKING_EVENT.BOOKING_EVENT, DOA_EVENT_ACTION.EVENT_ACTION, DOA_BOOKING_CODES.ACTIVE FROM `DOA_BOOKING_CODES` INNER JOIN `DOA_BOOKING_EVENT` ON DOA_BOOKING_CODES.PK_BOOKING_EVENT=DOA_BOOKING_EVENT.PK_BOOKING_EVENT INNER JOIN `DOA_EVENT_ACTION` ON DOA_BOOKING_CODES.PK_EVENT_ACTION=DOA_EVENT_ACTION.PK_EVENT_ACTION WHERE PK_ACCOUNT_MASTER='$_SESSION[PK_ACCOUNT_MASTER]'");
                                    while (!$row->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_BOOKING_CODES']?>);"><?=$row->fields['BOOKING_CODE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_BOOKING_CODES']?>);"><?=$row->fields['BOOKING_NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_BOOKING_CODES']?>);"><?=$row->fields['BOOKING_EVENT']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_BOOKING_CODES']?>);"><?=$row->fields['EVENT_ACTION']?></td>
                                            <td>
                                                <a href="add_booking_codes.php?id=<?=$row->fields['PK_BOOKING_CODES']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php if($row->fields['ACTIVE']==1){ ?>
                                                    <span class="active-box-green"></span>
                                                <?php } else{ ?>
                                                    <span class="active-box-red"></span>
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
<script>
    $(function () {
        $('#myTable').DataTable();
    });
    function ConfirmDelete(anchor)
    {
        var conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=anchor.attr("href");
    }
    function editpage(id){
        //alert(i);
        window.location.href = "add_booking_codes.php?id="+id;
    }
</script>
</body>
</html>