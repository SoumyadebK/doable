<?php
require_once('../global/config.php');
$title = "All Alert Messages";

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
    $search = " AND (DOA_ALERT_MESSAGES.ALERT_MESSAGES LIKE '%".$search_text."%')";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db->Execute("SELECT count(DOA_ALERT_MESSAGES.PK_ALERT_MESSAGES) AS TOTAL_RECORDS FROM DOA_ALERT_MESSAGES");
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
                        <div class="col-md-5 align-self-center">
                            <h4 class="text-themecolor"><?=$title?></h4>
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
                        <div class="col-md-4 align-self-center text-end">
                            <div class="d-flex justify-content-end align-items-center">
                                <ol class="breadcrumb justify-content-end">
                                    <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                    <li class="breadcrumb-item active"><?=$title?></li>
                                </ol>
                                <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='add_alert_message.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
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
                                                    <th>Message Type</th>
                                                    <th>Alert Messages</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                             <?php
                                                    $i=1;
                                                    $result_dropdown_query = mysqli_query($conn,"SELECT ALERT_MESSAGES,MESSAGE_TYPE,PK_ALERT_MESSAGES FROM `DOA_ALERT_MESSAGES` WHERE DOA_ALERT_MESSAGES.ACTIVE=1 ".$search." LIMIT " . $page_first_result . ',' . $results_per_page);
                                                    while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { ?>

                                                <tr>
                                                    <td><?=$i;?></td>
                                                    <td><?=$result_dropdown['MESSAGE_TYPE']?></td>
                                                    <td><?=$result_dropdown['ALERT_MESSAGES']?></td>

                                                </tr>
                                                <?php
                                                    $i++;
                                                    }
                                                    ?>
                                            </tbody>
                                        </table>
                                        <div class="center">
                                            <div class="pagination outer">
                                                <ul>
                                                    <?php if ($page > 1) { ?>
                                                        <li><a href="all_alert_messages.php?status=<?=$status_check?>&page=1">&laquo;</a></li>
                                                        <li><a href="all_alert_messages.php?status=<?=$status_check?>&page=<?=($page-1)?>">&lsaquo;</a></li>
                                                    <?php }
                                                    for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                                                        if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                                                            echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="all_alert_messages.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                        } elseif ($page_count == ($number_of_page-1)){
                                                            echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                        } else {
                                                            echo '<li><a class="hidden" href="all_alert_messages.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                        }
                                                    }
                                                    if ($page < $number_of_page) { ?>
                                                        <li><a href="all_alert_messages.php?status=<?=$status_check?>&page=<?=($page+1)?>">&rsaquo;</a></li>
                                                        <li><a href="all_alert_messages.php?status=<?=$status_check?>&page=<?=$number_of_page?>">&raquo;</a></li>
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
            $('#myTable').DataTable();
           });

        </script>
		
	</body>
</html>