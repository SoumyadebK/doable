<?php
require_once('../global/config.php');
$title = "All Accounts";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
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
                                                    <th>Business Name</th>
                                                    <th>Business type</th>
                                                    <th>City</th>
                                                    <th>Phone No.</th>
                                                    <th>Email</th>
                                                    <th>Joined On</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    $i=1;
                                                    $row = $db->Execute("SELECT DOA_ACCOUNT_MASTER.*, DOA_BUSINESS_TYPE.BUSINESS_TYPE FROM DOA_ACCOUNT_MASTER LEFT JOIN DOA_BUSINESS_TYPE ON DOA_BUSINESS_TYPE.PK_BUSINESS_TYPE = DOA_ACCOUNT_MASTER.PK_BUSINESS_TYPE ORDER BY CREATED_ON DESC");
                                                    while (!$row->EOF) { ?>
                                                <tr>
                                                    <td onclick="editpage(<?=$row->fields['PK_ACCOUNT_MASTER'];?>);"><?=$i;?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_ACCOUNT_MASTER'];?>);"><?=$row->fields['BUSINESS_NAME']?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_ACCOUNT_MASTER'];?>);"><?=$row->fields['BUSINESS_TYPE']?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_ACCOUNT_MASTER'];?>);"><?=$row->fields['CITY']?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_ACCOUNT_MASTER'];?>);"><?=$row->fields['PHONE']?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_ACCOUNT_MASTER'];?>);"><?=$row->fields['EMAIL']?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_ACCOUNT_MASTER'];?>);"><?=date('m/d/Y', strtotime($row->fields['CREATED_ON']))?></td>
                                                    <td style="text-align: center;padding: 10px 0px 0px 0px;font-size: 25px;">
                                                        <a href="account.php?id=<?=$row->fields['PK_ACCOUNT_MASTER']?>" style="color: #03a9f3;"><i class="ti-eye"></i></a>&nbsp;&nbsp;
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
                window.location.href = "account.php?id="+id;
            }
        </script>
    </body>
</html>