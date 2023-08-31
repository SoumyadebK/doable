<?php
require_once('../global/config.php');
$title = "All Enrollments";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$results_per_page = 100;

if (isset($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = " AND DOA_USERS.FIRST_NAME LIKE '%".$search_text."%' OR DOA_USERS.LAST_NAME LIKE '%".$search_text."%'OR DOA_USERS.EMAIL_ID LIKE '%".$search_text."%' OR DOA_USERS.PHONE LIKE '%".$search_text."%'";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db->Execute("SELECT count($account_database.DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TOTAL_RECORDS FROM $account_database.`DOA_ENROLLMENT_MASTER` INNER JOIN $master_database.DOA_USER_MASTER ON $account_database.DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $master_database.DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS ON DOA_USERS.PK_USER = $master_database.DOA_USER_MASTER.PK_USER LEFT JOIN $master_database.DOA_LOCATION ON $master_database.DOA_LOCATION.PK_LOCATION = $account_database.DOA_ENROLLMENT_MASTER.PK_LOCATION LEFT JOIN $account_database.DOA_ENROLLMENT_BALANCE ON $account_database.DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = $account_database.DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND $account_database.DOA_ENROLLMENT_MASTER.PK_ACCOUNT_MASTER=".$_SESSION['PK_ACCOUNT_MASTER'].$search." ORDER BY $master_database.DOA_USERS.FIRST_NAME");

$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}

$page_first_result = ($page-1) * $results_per_page;

if (isset($_POST['CANCEL_FUTURE_APPOINTMENT'])){
    $PK_ENROLLMENT_MASTER = $_POST['PK_ENROLLMENT_MASTER'];
    if ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 1){
        $UPDATE_DATA['STATUS'] = 'C';
        db_perform_account('DOA_APPOINTMENT_MASTER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
        db_perform_account('DOA_ENROLLMENT_MASTER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
    }

    if ($_POST['CANCEL_FUTURE_BILLING'] == 1){
        $UPDATE_DATA['STATUS'] = 'C';
        db_perform_account('DOA_ENROLLMENT_LEDGER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");

        $PK_USER_MASTER = $_POST['PK_USER_MASTER'];
        if ($_POST['CREDIT_BALANCE'] > 0) {
            $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
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
            db_perform_account('DOA_CUSTOMER_WALLET', $INSERT_DATA, 'insert');

            $enrollment_balance = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
            if ($enrollment_balance->RecordCount() > 0) {
                $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_USED'] = $enrollment_balance->fields['TOTAL_BALANCE_USED'] + $_POST['CREDIT_BALANCE'];
                $ENROLLMENT_BALANCE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
                $ENROLLMENT_BALANCE_DATA['EDITED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'update', " PK_ENROLLMENT_MASTER =  '$_POST[PK_ENROLLMENT_MASTER]'");
            }
        }
    }
    header('location:all_enrollments.php');
}

if(!empty($_GET['id']) && !empty($_GET['status'])) {
    if ($_GET['status'] == 'active') {
        $PK_ENROLLMENT_MASTER = $_GET['id'];
        $UPDATE_DATA['STATUS'] = 'A';
        db_perform_account('DOA_APPOINTMENT_MASTER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
        db_perform_account('DOA_ENROLLMENT_MASTER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
        db_perform_account('DOA_ENROLLMENT_LEDGER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
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
        <div class="container-fluid body_content">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-5 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='enrollment.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
                    </div>
                </div>
                <div class="col-md-2 align-self-center text-end">
                    <form class="form-material form-horizontal" action="" method="get">
                        <div class="input-group">
                            <input class="form-control" type="text" name="search_text" placeholder="Search.." value="<?=$search_text?>">
                            <button class="btn btn-info waves-effect waves-light m-r-10 text-white input-group-btn m-b-1" type="submit"><i class="fa fa-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table  class="table table-striped border" data-page-length='50'>
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
                                    $i=$page_first_result+1;
                                    $row = $db->Execute("SELECT DISTINCT $account_database.DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, $account_database.DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, $account_database.DOA_ENROLLMENT_MASTER.ACTIVE, $account_database.DOA_ENROLLMENT_MASTER.STATUS, $account_database.DOA_ENROLLMENT_MASTER.PK_USER_MASTER, $master_database.DOA_USERS.FIRST_NAME, $master_database.DOA_USERS.LAST_NAME, $master_database.DOA_USERS.EMAIL_ID, $master_database.DOA_USERS.PHONE, $master_database.DOA_LOCATION.LOCATION_NAME, $account_database.DOA_ENROLLMENT_BALANCE.TOTAL_BALANCE_PAID, $account_database.DOA_ENROLLMENT_BALANCE.TOTAL_BALANCE_USED, $master_database.DOA_USER_MASTER.PK_USER_MASTER, $account_database.DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM $account_database.`DOA_ENROLLMENT_MASTER` INNER JOIN $master_database.DOA_USER_MASTER ON $account_database.DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $master_database.DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS ON $master_database.DOA_USERS.PK_USER = $master_database.DOA_USER_MASTER.PK_USER LEFT JOIN $master_database.DOA_LOCATION ON $master_database.DOA_LOCATION.PK_LOCATION = $account_database.DOA_ENROLLMENT_MASTER.PK_LOCATION LEFT JOIN $account_database.DOA_ENROLLMENT_BALANCE ON $account_database.DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = $account_database.DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER INNER JOIN $account_database.DOA_ENROLLMENT_SERVICE ON $account_database.DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = $account_database.DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND $account_database.DOA_ENROLLMENT_MASTER.PK_ACCOUNT_MASTER=".$_SESSION['PK_ACCOUNT_MASTER'].$search." ORDER BY $master_database.DOA_USERS.FIRST_NAME LIMIT " . $page_first_result . ',' . $results_per_page);
                                    while (!$row->EOF) {
                                        $total_credit_balance = ($row->fields['TOTAL_BALANCE_PAID'])?($row->fields['TOTAL_BALANCE_PAID']-$row->fields['TOTAL_BALANCE_USED']):0;
                                        $number_of_session = $row->fields['NUMBER_OF_SESSION'];
                                        $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                                        $serviceCode = [];
                                        while (!$serviceCodeData->EOF) {
                                        $serviceCode[] = $serviceCodeData->fields['SERVICE_CODE'].': '.$serviceCodeData->fields['NUMBER_OF_SESSION'];
                                        $serviceCodeData->MoveNext();
                                        }
                                        ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['ENROLLMENT_ID']." || ".implode(', ', $serviceCode)?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['FIRST_NAME']." ".$row->fields['LAST_NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['EMAIL_ID']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['PHONE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['LOCATION_NAME']?></td>
                                            <td>
                                                <a href="enrollment.php?id=<?=$row->fields['PK_ENROLLMENT_MASTER']?>" title="Edit" style="font-size:18px"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                                                <?php if($row->fields['ACTIVE']==1){ ?>
                                                    <span class="active-box-green"></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php } else{ ?>
                                                    <span class="active-box-red"></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php } ?>

                                                <a href="enrollment.php?customer_id=<?=$row->fields['PK_USER_MASTER']?>" title="Add Enrollment" style="font-size:18px"><i class="fa fa-plus-circle"></i></a>
                                            </td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);">
                                                <?php if ($row->fields['STATUS']=='A') { ?>
                                                    <i class="fa fa-check-circle" style="font-size:21px;color:#35e235;"></i>
                                                <?php } else { ?>
                                                    <span class="fa fa-check-circle" style="font-size:21px;color:#ff0000;"></span>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <?php if ($row->fields['STATUS']=='A') { ?>
                                                    <a href="javascript:;" onclick="cancelAppointment(<?=$row->fields['PK_ENROLLMENT_MASTER']?>, <?=$row->fields['PK_USER_MASTER']?>, <?=$total_credit_balance?>)"><img src="../assets/images/noun-cancel-button.png" alt="LOGO" style="height: 21px; width: 21px;"></a>
                                                <?php } else { ?>
                                                    <a href="all_enrollments.php?id=<?=$row->fields['PK_ENROLLMENT_MASTER']?>&status=active">Active Enrollment</a>
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
                                                <li><a href="all_enrollments.php?page=1">&laquo;</a></li>
                                                <li><a href="all_enrollments.php?page=<?=($page-1)?>">&lsaquo;</a></li>
                                            <?php }
                                            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                                                if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                                                    echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="all_enrollments.php?page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                } elseif ($page_count == ($number_of_page-1)){
                                                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                } else {
                                                    echo '<li><a class="hidden" href="all_enrollments.php?page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                }
                                            }
                                            if ($page < $number_of_page) { ?>
                                                <li><a href="all_enrollments.php?page=<?=($page+1)?>">&rsaquo;</a></li>
                                                <li><a href="all_enrollments.php?page=<?=$number_of_page?>">&raquo;</a></li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </div>
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