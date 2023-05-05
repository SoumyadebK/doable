<?php
require_once('../global/config.php');
$title = "All Customers";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$results_per_page = 2;

$query = $db->Execute("SELECT count(DOA_USERS.PK_USER) AS TOTAL_RECORDS FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USERS.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);

$number_of_result =  $query->fields['TOTAL_RECORDS'];

$number_of_page = ceil ($number_of_result / $results_per_page);

//determine which page number visitor is currently on
if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}

//determine the sql LIMIT starting number for the results on the displaying page
$page_first_result = ($page-1) * $results_per_page;

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    body {
        font-family: Arial;
    }

    * {
        box-sizing: border-box;
    }

    form.example input[type=text] {
        padding: 10px;
        font-size: 10px;
        border: 1px solid grey;
        float: left;
        width: 60%;
        background: #f1f1f1;
    }

    form.example button {
        float: left;
        width: 15%;
        padding: 10px;
        background: #4CAF50;
        color: white;
        font-size: 10px;
        border: 1px solid grey;
        border-left: none;
        cursor: pointer;
    }

    form.example button:hover {
        background: #0bda68;
    }

    form.example::after {
        content: "";
        clear: both;
        display: table;
    }

    /*pagination*/
    .center {
        text-align: center;
    }

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
        background-color: #4CAF50;
        color: white;
        border: 1px solid #4CAF50;
    }

    .pagination a:hover:not(.active) {background-color: #ddd;}
</style>
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
                            <div class="col-12">
                                <div>
                                    <h5 class="card-title"><?=$title?></h5>
                                </div>
                                <div class="center">
                                    <form class="example" action="/action_page.php" style="margin-left:1250px; margin-bottom: 10px; max-width:200px;">
                                        <input type="text" placeholder="Search.." name="search2">
                                        <button type="submit"><i class="fa fa-search"></i></button>
                                    </form>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table  class="table table-striped border" data-page-length='50'>
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
                                    $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_ID, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USERS.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." LIMIT " . $page_first_result . ',' . $results_per_page);
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
                                <div class="center">
                                    <div class="pagination">
                                        <a href="all_customers.php">&laquo;</a>
                                        <?php
                                        //display the link of the pages in URL
                                        for($page = 1; $page<= $number_of_page; $page++) {
                                            echo '<a class="active" href = "all_customers.php?page=' . $page . '">' . $page . ' </a>';
                                        } ?>
                                        <a href="all_customers.php">&raquo;</a>
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