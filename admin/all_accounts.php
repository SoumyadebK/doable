<?php
require_once('../global/config.php');
$title = "All Accounts";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
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
                <div class="container-fluid body_content">
                    <div class="row page-titles">
                        <div class="col-md-5 align-self-center">
                            <h4 class="text-themecolor"><?=$title?></h4>
                        </div>
                        <div class="col-md-7 align-self-center text-end">
                            <div class="d-flex justify-content-end align-items-center">
                                <ol class="breadcrumb justify-content-end">
                                    <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                                    <li class="breadcrumb-item active"><?=$title?></li>
                                </ol>

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
                                                    <th>Business type</th>
                                                    <th>Business Name</th>
                                                    <th>City</th>
                                                    <th>Phone No.</th>
                                                    <th>Email</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                             <?php
                                                    $i=1;
                                                    $result_dropdown_query = mysqli_query($conn,"SELECT DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER,BUSINESS_NAME,DOA_BUSINESS_TYPE.BUSINESS_TYPE,API_KEY,CITY,DOA_COUNTRY.COUNTRY_NAME,DOA_STATES.STATE_NAME,PHONE,EMAIL 
                                                    FROM DOA_ACCOUNT_MASTER LEFT OUTER JOIN DOA_BUSINESS_TYPE ON DOA_BUSINESS_TYPE.PK_BUSINESS_TYPE=DOA_ACCOUNT_MASTER.PK_BUSINESS_TYPE 
                                                    LEFT OUTER JOIN DOA_COUNTRY ON DOA_ACCOUNT_MASTER.PK_COUNTRY=DOA_COUNTRY.PK_COUNTRY 
                                                    LEFT OUTER JOIN DOA_STATES ON DOA_ACCOUNT_MASTER.PK_STATES=DOA_STATES.PK_STATES WHERE DOA_ACCOUNT_MASTER.ACTIVE=1 AND DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER='$_SESSION[PK_ACCOUNT_MASTER]'");
                                                    while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { ?>

                                                <tr>
                                                    <td onclick="editpage(<?=$result_dropdown['PK_ACCOUNT_MASTER'];?>);"><?=$i;?></td>
                                                    <td onclick="editpage(<?=$result_dropdown['PK_ACCOUNT_MASTER'];?>);"><?=$result_dropdown['BUSINESS_TYPE']?></td>
                                                    <td onclick="editpage(<?=$result_dropdown['PK_ACCOUNT_MASTER'];?>);"><?=$result_dropdown['BUSINESS_NAME']?></td>
                                                     <td onclick="editpage(<?=$result_dropdown['PK_ACCOUNT_MASTER'];?>);"><?=$result_dropdown['CITY']?></td>
                                                    <td onclick="editpage(<?=$result_dropdown['PK_ACCOUNT_MASTER'];?>);"><?=$result_dropdown['PHONE']?></td>
                                                    <td onclick="editpage(<?=$result_dropdown['PK_ACCOUNT_MASTER'];?>);"><?=$result_dropdown['EMAIL']?></td>
                                                    <td>
                                                 <a href="business_profile.php?id=<?=$result_dropdown['PK_ACCOUNT_MASTER']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                                                  </td>
                                                </tr>
                                                <?php
                                                    $i++;
                                                    }
                                                    ?>
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
	window.location.href = "add_account.php?id="+id;

}
    </script>
		
	</body>
</html>