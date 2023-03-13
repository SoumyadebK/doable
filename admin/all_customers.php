<?php
require_once('../global/config.php');
$title = "All Customers";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
} ?>

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
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='customer.php'"><i class="fa fa-plus-circle"></i> Create New</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?=$title?></h5>
                            <div class="table-responsive">
                                <table id="myTable" class="table table-striped border" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th>No</th>
                                        <th style="width:20%;">Name</th>
                                        <th style="width:10%;">Username</th>
                                        <th style="width:20%;">Email Id</th>
                                        <th style="width:12%;">Phone</th>
                                        <th style="width:10%;">Total Paid</th>
                                        <th style="width:10%;">Balance</th>
                                        <th style="width:10%;">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_ID, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USERS.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                    while (!$row->EOF) {
                                        $balance_data = $db->Execute("SELECT SUM(TOTAL_BALANCE_PAID) AS TOTAL_PAID, SUM(TOTAL_BALANCE_USED) AS BALANCE_USED FROM `DOA_ENROLLMENT_BALANCE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_USER_MASTER.PK_USER = ".$row->fields['PK_USER']);
                                        $total_paid = 0.00;
                                        $balance_left = 0.00;
                                        if ($balance_data->RecordCount() > 0) {
                                            $total_paid = $balance_data->fields['TOTAL_PAID'];
                                            $balance_left = $balance_data->fields['TOTAL_PAID']-$balance_data->fields['BALANCE_USED'];
                                        } ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=$row->fields['NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=$row->fields['USER_ID']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=$row->fields['EMAIL_ID']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=$row->fields['PHONE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($total_paid, 2)?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($balance_left, 2)?></td>
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