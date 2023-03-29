<?php
require_once('../global/config.php');
$title = "All Enrollments";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if (isset($_POST['CANCEL_FUTURE_APPOINTMENT'])){
    $PK_ENROLLMENT_MASTER = $_POST['PK_ENROLLMENT_MASTER'];
    if ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 1){
        $UPDATE_DATA['STATUS'] = 'C';
        db_perform('DOA_APPOINTMENT_MASTER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
        db_perform('DOA_ENROLLMENT_MASTER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
    }

    if ($_POST['CANCEL_FUTURE_BILLING'] == 1){
        $UPDATE_DATA['STATUS'] = 'C';
        db_perform('DOA_ENROLLMENT_LEDGER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");

        $PK_USER_MASTER = $_POST['PK_USER_MASTER'];
        if ($_POST['CREDIT_BALANCE'] > 0) {
            $wallet_data = $db->Execute("SELECT * FROM DOA_USER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_USER_WALLET DESC LIMIT 1");
            if ($wallet_data->RecordCount() > 0) {
                $INSERT_DATA['CURRENT_BALANCE'] = $wallet_data->fields['CURRENT_BALANCE'] + $_POST['CREDIT_BALANCE'];
            } else {
                $INSERT_DATA['CURRENT_BALANCE'] = $_POST['CREDIT_BALANCE'];
            }
            $INSERT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
            $INSERT_DATA['CREDIT'] = $_POST['CREDIT_BALANCE'];
            $INSERT_DATA['DESCRIPTION'] = "Balance credited for cancellation of enrollment ".$PK_ENROLLMENT_MASTER;
            $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_USER_WALLET', $INSERT_DATA, 'insert');

            $enrollment_balance = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
            if ($enrollment_balance->RecordCount() > 0) {
                $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_USED'] = $enrollment_balance->fields['TOTAL_BALANCE_USED'] + $_POST['CREDIT_BALANCE'];
                $ENROLLMENT_BALANCE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
                $ENROLLMENT_BALANCE_DATA['EDITED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'update', " PK_ENROLLMENT_MASTER =  '$_POST[PK_ENROLLMENT_MASTER]'");
            }
        }

    }

    header('location:all_enrollments.php');
}

if(!empty($_GET['id']) && !empty($_GET['status'])) {

    if ($_GET['status'] == 'active') {
        $PK_ENROLLMENT_MASTER = $_GET['id'];
        $UPDATE_DATA['STATUS'] = 'A';
        db_perform('DOA_APPOINTMENT_MASTER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
        db_perform('DOA_ENROLLMENT_MASTER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
        db_perform('DOA_ENROLLMENT_LEDGER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
        header('location:all_enrollments.php');
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
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='enrollment.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
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
                                        <th>Enrollment Id</th>
                                        <th>Customer</th>
                                        <th>Email ID</th>
                                        <th>Phone</th>
                                        <th>Location</th>
                                        <th>Actions</th>
                                        <th>Status</th>
                                        <th>Cancel</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ACTIVE, DOA_ENROLLMENT_MASTER.STATUS, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_LOCATION.LOCATION_NAME, DOA_ENROLLMENT_BALANCE.TOTAL_BALANCE_PAID, DOA_ENROLLMENT_BALANCE.TOTAL_BALANCE_USED 
                                        FROM `DOA_ENROLLMENT_MASTER` 
                                        INNER JOIN DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER 
                                        INNER JOIN DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER 
                                        LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION
                                        LEFT JOIN DOA_ENROLLMENT_BALANCE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER 
                                        WHERE DOA_ENROLLMENT_MASTER.PK_ACCOUNT_MASTER='$_SESSION[PK_ACCOUNT_MASTER]' 
                                        ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC");
                                    while (!$row->EOF) {
                                        $total_credit_balance = ($row->fields['TOTAL_BALANCE_PAID'])?($row->fields['TOTAL_BALANCE_PAID']-$row->fields['TOTAL_BALANCE_USED']):0; ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['ENROLLMENT_ID']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['FIRST_NAME']." ".$row->fields['LAST_NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['EMAIL_ID']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['PHONE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['LOCATION_NAME']?></td>
                                            <td>
                                                <a href="enrollment.php?id=<?=$row->fields['PK_ENROLLMENT_MASTER']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php if($row->fields['ACTIVE']==1){ ?>
                                                    <span class="active-box-green"></span>
                                                <?php } else{ ?>
                                                    <span class="active-box-red"></span>
                                                <?php } ?>
                                            </td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);">
                                                <?php if ($row->fields['STATUS']=='A') { ?>
                                                    <span class="status-box" style="background-color: green;">ACTIVE</span>
                                                <?php } else { ?>
                                                    <span class="status-box" style="background-color: red;">CANCELLED</span>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <?php if ($row->fields['STATUS']=='A') { ?>
                                                    <a href="javascript:;" onclick="cancelAppointment(<?=$row->fields['PK_ENROLLMENT_MASTER']?>, <?=$row->fields['PK_USER_MASTER']?>, <?=$total_credit_balance?>)">Cancel Enrollment</a>
                                                <?php } else { ?>
                                                    <a href="all_enrollments.php?id=<?=$row->fields['PK_ENROLLMENT_MASTER']?>&status=active">Active Enrollment</a>
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

            <!--Cancel appointment model-->
            <div id="myModal" class="modal">
                <!-- Modal content -->
                <div class="modal-content" style="width: 50%;">
                    <span class="close" style="margin-left: 96%;">&times;</span>
                    <div class="card">
                        <div class="card-body">
                            <h4><b>Cancel Enrollment</b></h4>
                            <form class="p-20" action="" method="post">
                                <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER">
                                <input type="hidden" name="PK_USER_MASTER" class="PK_USER_MASTER">
                                <input type="hidden" name="CREDIT_BALANCE" class="CREDIT_BALANCE">
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Cancel Future Appointments?</label>
                                        </div>
                                        <div class="col-md-2">
                                            <label><input type="radio" name="CANCEL_FUTURE_APPOINTMENT" value="1" required/>&nbsp;Yes</label>&nbsp;&nbsp;
                                            <label><input type="radio" name="CANCEL_FUTURE_APPOINTMENT" value="0" required/>&nbsp;No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Cancel Future Billing?</label>
                                        </div>
                                        <div class="col-md-2">
                                            <label><input type="radio" name="CANCEL_FUTURE_BILLING" value="1" required/>&nbsp;Yes</label>&nbsp;&nbsp;
                                            <label><input type="radio" name="CANCEL_FUTURE_BILLING" value="0" required/>&nbsp;No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <b>Note: Credit balance $<span id="total_credit_balance"></span> will be moved  to Wallet.</b>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once('../includes/footer.php');?>
<script>
    // Get the modal
    var modal = document.getElementById("myModal");

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // When the user clicks the button, open the modal
    function openModel() {
        modal.style.display = "block";
    }

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

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
        window.location.href = "enrollment.php?id="+id;
    }
    
    function cancelAppointment(PK_ENROLLMENT_MASTER, PK_USER_MASTER, total_credit_balance) {
        $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
        $('.PK_USER_MASTER').val(PK_USER_MASTER);
        $('.CREDIT_BALANCE').val(total_credit_balance);
        $('#total_credit_balance').text(parseFloat(total_credit_balance).toFixed(2));
        openModel();
    }
</script>
</body>
</html>