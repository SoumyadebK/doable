<?php
require_once('../global/config.php');
$title = "All Countries";

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
                <div class="container-fluid v">
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
                                <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='add_country.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
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
                                                    <th>Country Code</th>
                                                    <th>Country Name</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                             <?php
                                                    $i=1;
                                                    $result_dropdown_query = mysqli_query($conn,"SELECT COUNTRY_CODE,COUNTRY_NAME,PK_COUNTRY FROM `DOA_COUNTRY` Where ACTIVE=1");
                                                    while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { ?>

                                                <tr>
                                                    <td onclick="editpage(<?=$result_dropdown['PK_COUNTRY'];?>);"><?=$i;?></td>
                                                    <td onclick="editpage(<?=$result_dropdown['PK_COUNTRY'];?>);"><?=$result_dropdown['COUNTRY_CODE']?></td>
                                                 <td onclick="editpage(<?=$result_dropdown['PK_COUNTRY'];?>);"><?=$result_dropdown['COUNTRY_NAME']?></td>
                                                    <td>
                                                 <a href="add_country.php?id=<?=$result_dropdown['PK_COUNTRY']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                  <a href="../Delete_Country.php?PK_COUNTRY=<?=$result_dropdown['PK_COUNTRY']?>" onclick='javascript:ConfirmDelete($(this));return false;'><img src="../assets/images/delete.png" title="Delete" style="padding-top:3px"></a>
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
	window.location.href = "add_country.php?id="+id;

}
    </script>
	</body>
</html>