<?php
require_once('../global/config.php');
$title = "All Gift Certificates";

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
                                    $i=1;
                                    $row = $db->Execute("SELECT DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_CODE, DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_NAME, DOA_GIFT_CERTIFICATE_MASTER.DATE_OF_PURCHASE, DOA_GIFT_CERTIFICATE_MASTER.AMOUNT, DOA_GIFT_CERTIFICATE_MASTER.ACTIVE FROM `DOA_GIFT_CERTIFICATE_MASTER` INNER JOIN `DOA_GIFT_CERTIFICATE_SETUP` ON DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_SETUP=DOA_GIFT_CERTIFICATE_SETUP.PK_GIFT_CERTIFICATE_SETUP LEFT JOIN `DOA_USER_MASTER` ON DOA_GIFT_CERTIFICATE_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN `DOA_USERS` ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_GIFT_CERTIFICATE_MASTER.PK_ACCOUNT_MASTER='$_SESSION[PK_ACCOUNT_MASTER]' ORDER BY DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_MASTER DESC");
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