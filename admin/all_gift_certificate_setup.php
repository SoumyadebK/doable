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
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='gift_certificate_setup.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
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
                                        <th>Gift Certificate Name</th>
                                        <th>Gift Certificate Code</th>
                                        <th>Minimum Amount</th>
                                        <th>Maximum Amount</th>
                                        <th>Effective Date</th>
                                        <th>End Date</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db->Execute("SELECT PK_GIFT_CERTIFICATE_SETUP, GIFT_CERTIFICATE_CODE, GIFT_CERTIFICATE_NAME, EFFECTIVE_DATE, END_DATE, MINIMUM_AMOUNT, MAXIMUM_AMOUNT, ACTIVE FROM `DOA_GIFT_CERTIFICATE_SETUP` WHERE PK_ACCOUNT_MASTER='$_SESSION[PK_ACCOUNT_MASTER]'");
                                    while (!$row->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_GIFT_CERTIFICATE_SETUP']?>);"><?=$row->fields['GIFT_CERTIFICATE_NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_GIFT_CERTIFICATE_SETUP']?>);"><?=$row->fields['GIFT_CERTIFICATE_CODE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_GIFT_CERTIFICATE_SETUP']?>);"><?=$row->fields['MINIMUM_AMOUNT']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_GIFT_CERTIFICATE_SETUP']?>);"><?=$row->fields['MAXIMUM_AMOUNT']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_GIFT_CERTIFICATE_SETUP']?>);"><?=$row->fields['EFFECTIVE_DATE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_GIFT_CERTIFICATE_SETUP']?>);"><?=$row->fields['END_DATE']?></td>
                                            <td>
                                                <a href="gift_certificate_setup.php?id=<?=$row->fields['PK_GIFT_CERTIFICATE_SETUP']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
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
        window.location.href = "gift_certificate_setup.php?id="+id;
    }
</script>
</body>
</html>