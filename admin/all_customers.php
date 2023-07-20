<?php
require_once('../global/config.php');
$title = "All Customers";

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
    $search = " AND (DOA_USERS.FIRST_NAME LIKE '%".$search_text."%' OR DOA_USERS.LAST_NAME LIKE '%".$search_text."%' OR DOA_USERS.USER_ID LIKE '%".$search_text."%' OR DOA_USERS.EMAIL_ID LIKE '%".$search_text."%' OR DOA_USERS.PHONE LIKE '%".$search_text."%')";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), count(DOA_USERS.PK_USER) AS TOTAL_RECORDS FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.ACTIVE = '$status' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search." ORDER BY DOA_USERS.FIRST_NAME");
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
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-2 align-self-center">
                    <?php if ($status_check=='inactive') { ?>
                    <h4 class="text-themecolor">Not Active Customers</h4>
                    <?php } elseif ($status_check=='active') { ?>
                    <h4 class="text-themecolor">Active Customers</h4>
                    <?php } ?>
                </div>

                <?php if ($status_check=='inactive') { ?>
                    <div class="col-md-3 align-self-center">
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_customers.php?status=active'"><i class="fa fa-user"></i> Show Active</button>
                    </div>
                <?php } elseif ($status_check=='active') { ?>
                    <div class="col-md-3 align-self-center">
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_customers.php?status=inactive'"><i class="fa fa-user-times"></i> Show Not Active</button>
                    </div>
                <?php } ?>

                <div class="col-md-4 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center" style="margin-right: 60%">
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='customer.php'"><i class="fa fa-plus-circle"></i> Create New</button>
                    </div>
                </div>
                <div class="col-md-3 align-self-center text-end">
                    <form class="form-material form-horizontal" action="" method="get">
                        <input type="hidden" name="status" value="<?=$status_check?>" >
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
                                        <th style="width:20%;">Name</th>
                                        <th style="width:10%;">Customer ID</th>
                                        <th style="width:20%;">Email Id</th>
                                        <th style="width:12%;">Phone</th>
                                        <th style="width:10%;">Total Paid</th>
                                        <th style="width:10%;">Balance</th>
                                        <th style="width:10%;">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $i = $page_first_result+1;
                                    $row = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_ID, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.ACTIVE = '$status' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search." ORDER BY DOA_USERS.FIRST_NAME LIMIT " . $page_first_result . ',' . $results_per_page );
                                    while (!$row->EOF) {
                                        $total_paid_data = $db->Execute("SELECT SUM(AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER=".$row->fields['PK_USER_MASTER']);
                                        $total_paid = ($total_paid_data->RecordCount() > 0) ? $total_paid_data->fields['TOTAL_PAID'] : 0.00;
                                        $total_used = 0.00;
                                        $row1 = $db->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ACTIVE FROM `DOA_ENROLLMENT_MASTER` WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER=".$row->fields['PK_USER_MASTER']);
                                        while (!$row1->EOF) {
                                            $used_session_count = $db->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT, PK_SERVICE_MASTER FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = ".$row1->fields['PK_ENROLLMENT_MASTER']);
                                            $PK_SERVICE_MASTER = ($used_session_count->RecordCount() > 0) ? $used_session_count->fields['PK_SERVICE_MASTER'] : 0;
                                            $total_session = $db->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = ".$row1->fields['PK_ENROLLMENT_MASTER']." AND `PK_SERVICE_MASTER` = ".$PK_SERVICE_MASTER);
                                            $total_session_count = ($total_session->RecordCount() > 0) ? $total_session->fields['TOTAL_SESSION_COUNT'] : 0;
                                            $total_bill_and_paid = $db->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=".$row1->fields['PK_ENROLLMENT_MASTER']);
                                            $price_per_session = ($total_session_count > 0) ? $total_bill_and_paid->fields['TOTAL_PAID']/$total_session_count : 0.00;
                                            $total_used += ($used_session_count->fields['USED_SESSION_COUNT']*$price_per_session);
                                            $row1->MoveNext();
                                        } ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=$row->fields['NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=$row->fields['USER_ID']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=$row->fields['EMAIL_ID']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=$row->fields['PHONE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($total_paid, 2)?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($total_paid-$total_used, 2)?></td>
                                            <td style="margin-top: auto; margin-bottom: auto">
                                                <?php if($row->fields['EMAIL_ID']): ?>
                                                    <a class="waves-dark" href="compose.php?sel_uid=<?=$row->fields['PK_USER']?>" aria-haspopup="true" aria-expanded="false" title="Email"><i class="ti-email" style="font-size: 20px;"></i>
                                                    </a>&nbsp;&nbsp;
                                                <?php else: ?>
                                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php endif; ?>    

                                                <a href="customer.php?id=<?=$row->fields['PK_USER']?>&master_id=<?=$row->fields['PK_USER_MASTER']?>"><i class="ti-pencil" style="font-size: 20px;"></i></a>&nbsp;&nbsp;
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
                                <div class="center">
                                    <div class="pagination outer">
                                        <ul>
                                            <?php if ($page > 1) { ?>
                                                <li><a href="all_customers.php?status=<?=$status_check?>&page=1">&laquo;</a></li>
                                                <li><a href="all_customers.php?status=<?=$status_check?>&page=<?=($page-1)?>">&lsaquo;</a></li>
                                            <?php }
                                            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                                                if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                                                    echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="all_customers.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                } elseif ($page_count == ($number_of_page-1)){
                                                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                } else {
                                                    echo '<li><a class="hidden" href="all_customers.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                }
                                            }
                                            if ($page < $number_of_page) { ?>
                                                <li><a href="all_customers.php?status=<?=$status_check?>&page=<?=($page+1)?>">&rsaquo;</a></li>
                                                <li><a href="all_customers.php?status=<?=$status_check?>&page=<?=$number_of_page?>">&raquo;</a></li>
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

<script>
    $(function () {
        $('#myTable').DataTable({
            "columnDefs": [
                { "targets": [0,2,5], "searchable": false }
            ]
        });
    });
    function ConfirmDelete(anchor)
    {
        let conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=anchor.attr("href");
    }
    function editpage(id, master_id){
        window.location.href = "customer.php?id="+id+"&master_id="+master_id;

    }
</script>

</body>
</html>