<?php

use Twilio\TwiML\Voice\Echo_;

require_once('../global/config.php');
$title = "All Accounts";

$status_check = empty($_GET['status']) ? 'active' : $_GET['status'];

if ($status_check == 'active') {
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1) {
    header("location:../login.php");
    exit;
}

$results_per_page = 100;

if (isset($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = " AND (DOA_USERS.PK_ACCOUNT_MASTER LIKE '%" . $search_text . "%' OR DOA_USERS.FIRST_NAME LIKE '%" . $search_text . "%' OR DOA_USERS.LAST_NAME LIKE '%" . $search_text . "%' OR DOA_USERS.USER_NAME LIKE '%" . $search_text . "%' OR DOA_USERS.EMAIL_ID LIKE '%" . $search_text . "%')";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db->Execute("SELECT COUNT(DOA_USERS.PK_USER) AS TOTAL_RECORDS FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE (DOA_USER_ROLES.PK_ROLES = 2 || DOA_USER_ROLES.PK_ROLES = 11) " . $search . " AND DOA_USERS.ACTIVE = '$status' AND CREATED_BY = '$_SESSION[PK_USER]'");
$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil($number_of_result / $results_per_page);

if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = $_GET['page'];
}

$page_first_result = ($page - 1) * $results_per_page;
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <?php require_once('../includes/setup_menu_super_admin.php') ?>
            <div class="container-fluid body_content m-0">
                <div class="row page-titles">
                    <div class="col-md-4 align-self-center">
                        <?php if ($status_check == 'inactive') { ?>
                            <h4 class="text-themecolor">Not Active Accounts</h4>
                        <?php } elseif ($status_check == 'active') { ?>
                            <h4 class="text-themecolor">Active Accounts</h4>
                        <?php } ?>
                    </div>
                    <div class="col-md-3 align-self-center text-end">
                        <form class="form-material form-horizontal" action="" method="get">
                            <input type="hidden" name="status" value="<?= $status_check ?>">
                            <div class="input-group">
                                <input class="form-control" type="text" name="search_text" placeholder="Search.." value="<?= $search_text ?>">
                                <button class="btn btn-info waves-effect waves-light m-r-10 text-white input-group-btn m-b-1" type="submit"><i class="fa fa-search"></i></button>
                            </div>
                        </form>
                    </div>
                    <?php if ($status_check == 'inactive') { ?>
                        <div class="col-md-3 align-self-center">
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_accounts.php?status=active'"><i class="fa fa-user"></i> Show Active</button>
                        </div>
                    <?php } elseif ($status_check == 'active') { ?>
                        <div class="col-md-3 align-self-center">
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_accounts.php?status=inactive'"><i class="fa fa-user-times"></i> Show Not Active</button>
                        </div>
                    <?php } ?>
                    <div class="col-md-2 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='account.php'"><i class="fa fa-plus-circle"></i> Create New</button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped border">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Account ID</th>
                                                <th>Name</th>
                                                <th>User Name</th>
                                                <th>Phone No.</th>
                                                <th>Email</th>
                                                <th>Joined On</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;
                                            $row = $db->Execute("SELECT DOA_USERS.* FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE (DOA_USERS.IS_DELETED = 0 OR DOA_USERS.IS_DELETED IS NULL) AND DOA_USER_ROLES.PK_ROLES = 11 " . $search . " AND DOA_USERS.ACTIVE = '$status' AND CREATED_BY = '$_SESSION[PK_USER]' ORDER BY CREATED_ON DESC LIMIT " . $page_first_result . ',' . $results_per_page);
                                            while (!$row->EOF) {
                                                $phone = $row->fields['PHONE'];
                                                $formatted = preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $phone); ?>
                                                <tr>
                                                    <td onclick="editpage(<?= $row->fields['PK_ACCOUNT_MASTER']; ?>, <?= $row->fields['PK_USER']; ?>);"><?= $i; ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ACCOUNT_MASTER']; ?>, <?= $row->fields['PK_USER']; ?>);"><?= $row->fields['PK_ACCOUNT_MASTER']; ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ACCOUNT_MASTER']; ?>, <?= $row->fields['PK_USER']; ?>);"><?= $row->fields['FIRST_NAME'] . ' ' . $row->fields['LAST_NAME'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ACCOUNT_MASTER']; ?>, <?= $row->fields['PK_USER']; ?>);"><?= $row->fields['USER_NAME'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ACCOUNT_MASTER']; ?>, <?= $row->fields['PK_USER']; ?>);"><?= $formatted ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ACCOUNT_MASTER']; ?>, <?= $row->fields['PK_USER']; ?>);"><?= $row->fields['EMAIL_ID'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ACCOUNT_MASTER']; ?>, <?= $row->fields['PK_USER']; ?>);"><?= date('m/d/Y', strtotime($row->fields['CREATED_ON'])) ?></td>
                                                    <td style="text-align: center;padding: 10px 0px 0px 0px;font-size: 25px;">
                                                        <a href="account.php?id=<?= $row->fields['PK_ACCOUNT_MASTER'] ?>&user_id=<?= $row->fields['PK_USER']; ?>" style="color: #03a9f3;"><i class="ti-eye"></i></a>&nbsp;&nbsp;
                                                        <?php if ($row->fields['ACTIVE'] == 1) { ?>
                                                            <span class="active-box-green"></span>
                                                        <?php } else { ?>
                                                            <span class="active-box-red"></span>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php $row->MoveNext();
                                                $i++;
                                            } ?>
                                        </tbody>
                                    </table>
                                    <div class="center">
                                        <div class="pagination outer">
                                            <ul>
                                                <?php if ($page > 1) { ?>
                                                    <li><a href="all_accounts.php?status=<?= $status_check ?>&page=1">&laquo;</a></li>
                                                    <li><a href="all_accounts.php?status=<?= $status_check ?>&page=<?= ($page - 1) ?>">&lsaquo;</a></li>
                                                <?php }
                                                for ($page_count = 1; $page_count <= $number_of_page; $page_count++) {
                                                    if ($page_count == $page || $page_count == ($page + 1) || $page_count == ($page - 1) || $page_count == $number_of_page) {
                                                        echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="all_accounts.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                    } elseif ($page_count == ($number_of_page - 1)) {
                                                        echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                    } else {
                                                        echo '<li><a class="hidden" href="all_accounts.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                    }
                                                }
                                                if ($page < $number_of_page) { ?>
                                                    <li><a href="all_accounts.php?status=<?= $status_check ?>&page=<?= ($page + 1) ?>">&rsaquo;</a></li>
                                                    <li><a href="all_accounts.php?status=<?= $status_check ?>&page=<?= $number_of_page ?>">&raquo;</a></li>
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
    <?php require_once('../includes/footer.php'); ?>
    <script>
        $(function() {
            $('#myTable').DataTable();
        });

        function ConfirmDelete(anchor) {
            var conf = confirm("Are you sure you want to delete?");
            if (conf)
                window.location = anchor.attr("href");
        }

        function editpage(id, user_id) {
            window.location.href = "account.php?id=" + id + "&user_id=" + user_id;
        }
    </script>
</body>

</html>