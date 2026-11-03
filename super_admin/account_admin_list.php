<?php
require_once('../global/config.php');
$title = "Account Admin List";

$status_check = empty($_GET['status'])?'active':$_GET['status'];

if ($status_check == 'active'){
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

$results_per_page = 100;

if (isset($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = " AND (DOA_USERS.FIRST_NAME LIKE '%".$search_text."%' OR DOA_USERS.LAST_NAME LIKE '%".$search_text."%' OR DOA_USERS.PHONE LIKE '%".$search_text."%' OR DOA_USERS.EMAIL_ID LIKE '%".$search_text."%')";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db->Execute("SELECT count((DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER)) AS TOTAL_RECORDS FROM DOA_ACCOUNT_MASTER LEFT JOIN DOA_BUSINESS_TYPE ON DOA_BUSINESS_TYPE.PK_BUSINESS_TYPE = DOA_ACCOUNT_MASTER.PK_BUSINESS_TYPE");
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
                <?php require_once('../includes/setup_menu_super_admin.php') ?>
                <div class="container-fluid body_content m-0">
                    <div class="row page-titles">
                        <div class="col-md-4 align-self-center">
                            <?php if ($status_check=='inactive') { ?>
                                <h4 class="text-themecolor">Not Active Accounts</h4>
                            <?php } elseif ($status_check=='active') { ?>
                                <h4 class="text-themecolor">Active Accounts</h4>
                            <?php } ?>
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
                        <?php if ($status_check=='inactive') { ?>
                            <div class="col-md-3 align-self-center">
                                <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='account_admin_list.php?status=active'"><i class="fa fa-user"></i> Show Active</button>
                            </div>
                        <?php } elseif ($status_check=='active') { ?>
                            <div class="col-md-3 align-self-center">
                                <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='account_admin_list.php?status=inactive'"><i class="fa fa-user-times"></i> Show Not Active</button>
                            </div>
                        <?php } ?>
                        <div class="col-md-2 align-self-center text-end">
                            <div class="d-flex justify-content-end align-items-center">
                                <ol class="breadcrumb justify-content-end">
                                    <li class="breadcrumb-item active"><?=$title?></li>
                                </ol>
                                <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='account.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
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
                                                <th>Name</th>
                                                <th>Phone</th>
                                                <th>Email Id</th>
                                                <th>Actions</th>
                                            </tr>
                                            </thead>

                                            <tbody>
                                            <?php
                                            $i=1;
                                            $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.PHONE, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_ROLES.PK_ROLES IN(2) ".$search." AND DOA_USERS.ACTIVE = '$status'");
                                            while (!$row->EOF) {
                                                $selected_roles = [];
                                                if(!empty($row->fields['PK_USER'])) {
                                                    $PK_USER = $row->fields['PK_USER'];
                                                    $selected_roles_row = $db->Execute("SELECT DOA_ROLES.ROLES FROM `DOA_USER_ROLES` LEFT JOIN DOA_ROLES ON DOA_USER_ROLES.PK_ROLES = DOA_ROLES.PK_ROLES WHERE `PK_USER` = '$PK_USER'");
                                                    while (!$selected_roles_row->EOF) {
                                                        $selected_roles[] = $selected_roles_row->fields['ROLES'];
                                                        $selected_roles_row->MoveNext();
                                                    }
                                                } ?>
                                                <tr>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>);"><?=$i;?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>);"><?=$row->fields['NAME']?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>);"><?=$row->fields['PHONE']?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>);"><?=$row->fields['EMAIL_ID']?></td>
                                                    <td style="padding: 10px 0px 0px 0px;font-size: 20px;">
                                                        <a href="edit_account_user.php?id=<?=$row->fields['PK_USER']?>&ac_id=<?=$_GET['id']?>" title="Reset Password" style="color: #03a9f3;"><i class="ti-lock"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <?php if($row->fields['ACTIVE']==1){ ?>
                                                            <span title="Active" class="active-box-green"></span>
                                                        <?php } else{ ?>
                                                            <span title="Inactive" class="active-box-red"></span>
                                                        <?php } ?>&nbsp;&nbsp;
                                                        <a href="javascript:;" data-href="account.php?id=<?=$_GET['id']?>&PK_USER=<?=$row->fields['PK_USER']?>&cond=del" onclick="confirmDelete(this);" title="Delete" style="color: red;"><i class="ti-trash"></i></a>
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
                                                        <li><a href="all_accounts.php?status=<?=$status_check?>&page=1">&laquo;</a></li>
                                                        <li><a href="all_accounts.php?status=<?=$status_check?>&page=<?=($page-1)?>">&lsaquo;</a></li>
                                                    <?php }
                                                    for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                                                        if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                                                            echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="all_accounts.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                        } elseif ($page_count == ($number_of_page-1)){
                                                            echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                        } else {
                                                            echo '<li><a class="hidden" href="all_accounts.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                        }
                                                    }
                                                    if ($page < $number_of_page) { ?>
                                                        <li><a href="all_accounts.php?status=<?=$status_check?>&page=<?=($page+1)?>">&rsaquo;</a></li>
                                                        <li><a href="all_accounts.php?status=<?=$status_check?>&page=<?=$number_of_page?>">&raquo;</a></li>
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
    </body>
</html>

<script>
    $(function () {
        $('#myTable').DataTable();
    });

    function editpage(PK_USER, AC_ID){
        window.location.href = "edit_account_user.php?id="+PK_USER+"&ac_id="+AC_ID;
    }

    function confirmDelete(anchor)
    {
        let conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=$(anchor).data("href");
    }

</script>

