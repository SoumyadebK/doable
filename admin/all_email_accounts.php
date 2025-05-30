<?php
require_once('../global/config.php');
$title = "All Email Accounts";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Email Account page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
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
        <?php require_once('../includes/setup_menu.php') ?>
        <div class="container-fluid body_content m-0">
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
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='email_account.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row" style="text-align: center;">
                                <h5 style="font-weight: bold;"><?=$header_text?></h5>
                            </div>
                            <div class="table-responsive">
                                <table id="myTable" class="table table-striped border" data-page-length='50'>
                                    <thead>
                                        <tr>
                                            <th>Host</th>
                                            <th>Port</th>
                                            <th>User Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db_account->Execute("SELECT * FROM `DOA_EMAIL_ACCOUNT` LEFT JOIN DOA_EMAIL_ACCOUNT_LOCATION ON DOA_EMAIL_ACCOUNT.PK_EMAIL_ACCOUNT = DOA_EMAIL_ACCOUNT_LOCATION.PK_EMAIL_ACCOUNT WHERE DOA_EMAIL_ACCOUNT_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") GROUP BY DOA_EMAIL_ACCOUNT.PK_EMAIL_ACCOUNT ORDER BY DOA_EMAIL_ACCOUNT.PK_EMAIL_ACCOUNT DESC"); 
                                    while (!$row->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_EMAIL_ACCOUNT']?>);"><?=$row->fields['HOST']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_EMAIL_ACCOUNT']?>);"><?=$row->fields['PORT']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_EMAIL_ACCOUNT']?>);"><?=$row->fields['USER_NAME']?></td>
                                            <td>
                                                <a href="email_account.php?id=<?=$row->fields['PK_EMAIL_ACCOUNT']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
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
        window.location.href = "email_account.php?id="+id;
    }
</script>
</body>
</html>