<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Country";
else
    $title = "Edit Country";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
	
	$COUNTRY_NAME= $_POST['COUNTRY_NAME'];
	$COUNTRY_CODE= $_POST['COUNTRY_CODE'];
	$created_at=date('Y-m-d H:i:s');
	if($_GET['id'] == ''){
		$query=mysqli_query($conn,"insert into DOA_COUNTRY(COUNTRY_NAME,COUNTRY_CODE,CREATED_ON) 
		 values ('".$COUNTRY_NAME."','".$COUNTRY_CODE."','".$created_at."')");
	
	}else{
		
			$query= mysqli_query($conn,"update DOA_COUNTRY set COUNTRY_NAME='".$COUNTRY_NAME."',COUNTRY_CODE='".$COUNTRY_CODE."',EDITED_ON='".$created_at."' where PK_COUNTRY='".$_GET[id]."'");	
	  
	}
	header("location:all_countries.php");
}
	$COUNTRY_NAME	       = '';
	$COUNTRY_CODE	       = '';
if (isset($_GET['id'])) {
if($_GET['id'] == ''){
	$COUNTRY_NAME	       = '';
	$COUNTRY_CODE	       = '';
}
else {
$result_query = mysqli_query($conn,"SELECT * FROM DOA_COUNTRY WHERE PK_COUNTRY = '$_GET[id]'");
	$result=mysqli_fetch_array($result_query,MYSQLI_ASSOC);
	
	$COUNTRY_NAME       = $result['COUNTRY_NAME'];
	$COUNTRY_CODE	       = $result['COUNTRY_CODE'];
}
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
                                    <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                    <li class="breadcrumb-item"><a href="all_countries.php">All Countries</a></li>
                                    <li class="breadcrumb-item active"><?=$title?></li>
                                </ol>

                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">

                                    <form class="form-material form-horizontal m-t-30" name="form1" id="form1" action="" method="post" enctype="multipart/form-data">

                                    <div class="row">
                        <div class="col-6">

                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Country Code<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                               <input type="text" id="COUNTRY_CODE" name="COUNTRY_CODE" class="form-control" placeholder="enter Country Code" required data-validation-required-message="This field is required" value="<?php echo $COUNTRY_CODE?>">
                                            </div>
                                        </div>

                                        </div>
                                        <div class="col-6">
                                         <div class="form-group">
                                            <label class="col-md-12" for="example-text">Country Name<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="COUNTRY_NAME" name="COUNTRY_NAME" class="form-control" placeholder="enter Country Name" required data-validation-required-message="This field is required" value="<?php echo $COUNTRY_NAME?>">
                                            </div>
                                        </div>

                                        </div>
                                        </div>

                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                        <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_countries.php'">Cancel</button>
                                    </form>
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