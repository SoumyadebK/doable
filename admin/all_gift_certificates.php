<?php
require_once('../global/config.php');
$title = "All Gift Certificates";

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
    $search = " AND (DOA_USERS.FIRST_NAME LIKE '%".$search_text."%' OR DOA_USERS.LAST_NAME LIKE '%".$search_text."%' OR DOA_GIFT_CERTIFICATE_MASTER.GIFT_CERTIFICATE_NAME LIKE '%".$search_text."%' OR DOA_GIFT_CERTIFICATE_MASTER.GIFT_CERTIFICATE_CODE LIKE '%".$search_text."%' OR DOA_GIFT_CERTIFICATE_MASTER.AMOUNT LIKE '%".$search_text."%')";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db->Execute("SELECT count(DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_MASTER) AS TOTAL_RECORDS FROM `DOA_GIFT_CERTIFICATE_MASTER` INNER JOIN `DOA_GIFT_CERTIFICATE_SETUP` ON DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_SETUP=DOA_GIFT_CERTIFICATE_SETUP.PK_GIFT_CERTIFICATE_SETUP LEFT JOIN `DOA_USER_MASTER` ON DOA_GIFT_CERTIFICATE_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN `DOA_USERS` ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_GIFT_CERTIFICATE_MASTER.ACTIVE = '$status' AND DOA_GIFT_CERTIFICATE_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search);
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
                <div class="col-md-4 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-4 align-self-center text-end">
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
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='gift_certificate.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
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
                                        <th>Customer</th>
                                        <th>Gift Certificate Code</th>
                                        <th>Gift Certificate Name</th>
                                        <th>Date of Purchase</th>
                                        <th>Amount</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i = $page_first_result+1;
                                    $row = $db->Execute("SELECT DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_CODE, DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_NAME, DOA_GIFT_CERTIFICATE_MASTER.DATE_OF_PURCHASE, DOA_GIFT_CERTIFICATE_MASTER.AMOUNT, DOA_GIFT_CERTIFICATE_MASTER.ACTIVE FROM `DOA_GIFT_CERTIFICATE_MASTER` INNER JOIN `DOA_GIFT_CERTIFICATE_SETUP` ON DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_SETUP=DOA_GIFT_CERTIFICATE_SETUP.PK_GIFT_CERTIFICATE_SETUP LEFT JOIN `DOA_USER_MASTER` ON DOA_GIFT_CERTIFICATE_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN `DOA_USERS` ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_GIFT_CERTIFICATE_MASTER.ACTIVE = '$status' AND DOA_GIFT_CERTIFICATE_MASTER.PK_ACCOUNT_MASTER=".$_SESSION['PK_ACCOUNT_MASTER'].$search." ORDER BY DOA_GIFT_CERTIFICATE_MASTER.DATE_OF_PURCHASE DESC"." LIMIT " . $page_first_result . ',' . $results_per_page);
                                    while (!$row->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_GIFT_CERTIFICATE_MASTER']?>);"><?=(empty($row->fields['NAME'])?"":$row->fields['NAME'])?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_GIFT_CERTIFICATE_MASTER']?>);"><?=$row->fields['GIFT_CERTIFICATE_CODE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_GIFT_CERTIFICATE_MASTER']?>);"><?=$row->fields['GIFT_CERTIFICATE_NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_GIFT_CERTIFICATE_MASTER']?>);"><?=$row->fields['DATE_OF_PURCHASE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_GIFT_CERTIFICATE_MASTER']?>);"><?=$row->fields['AMOUNT']?></td>
                                            <td>
                                                <a href="gift_certificate.php?id=<?=$row->fields['PK_GIFT_CERTIFICATE_MASTER']?>"><i class="fa fa-edit" title="Edit" style="font-size:21px;"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php if($row->fields['ACTIVE']==1){ ?>
                                                    <span class="active-box-green"></span>
                                                <?php } else{ ?>
                                                    <span class="active-box-red"></span>
                                                <?php } ?>
                                                <a href="javascript:;" onclick="giftCertificate(<?=$row->fields['PK_GIFT_CERTIFICATE_MASTER']?>);"><i class="fa fa-download" title="Download" style="font-size:21px; padding-left: 15px"></i></a>
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
                                                <li><a href="all_gift_certificates.php?status=<?=$status_check?>&page=1">&laquo;</a></li>
                                                <li><a href="all_gift_certificates.php?status=<?=$status_check?>&page=<?=($page-1)?>">&lsaquo;</a></li>
                                            <?php }
                                            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                                                if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                                                    echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="all_gift_certificates.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                } elseif ($page_count == ($number_of_page-1)){
                                                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                } else {
                                                    echo '<li><a class="hidden" href="all_gift_certificates.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                }
                                            }
                                            if ($page < $number_of_page) { ?>
                                                <li><a href="all_gift_certificates.php?status=<?=$status_check?>&page=<?=($page+1)?>">&rsaquo;</a></li>
                                                <li><a href="all_gift_certificates.php?status=<?=$status_check?>&page=<?=$number_of_page?>">&raquo;</a></li>
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
    // $(function () {
    //     $('#myTable').DataTable();
    // });

    function ConfirmDelete(anchor)
    {
        var conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=anchor.attr("href");
    }

    function editpage(id){
        //alert(i);
        window.location.href = "gift_certificate.php?id="+id;
    }

    function giftCertificate(PK_GIFT_CERTIFICATE_MASTER) {
        //alert(PK_GIFT_CERTIFICATE_MASTER)
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: {FUNCTION_NAME: 'viewGiftCertificatePdf', PK_GIFT_CERTIFICATE_MASTER: PK_GIFT_CERTIFICATE_MASTER},
            success:function (data) {
                window.open(
                    data,
                    '_blank' // <- This is what makes it open in a new window.
                );
            },
            error: (error) => {
                console.log(JSON.stringify(error));
            }
        });

    }
</script>
</body>
</html>