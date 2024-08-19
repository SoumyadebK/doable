<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Alert Message";
else
    $title = "Edit Alert Message";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
	
	$SETTINGS= $_POST['SETTINGS'];
	$VALUE=$_POST['VALUE'];
	$created_at=date('Y-m-d H:i:s');
	if($_GET['id'] == ''){
		
		$query=mysqli_query($conn,"insert into DOA_ALERT_MESSAGES(MESSAGE_TYPE,ALERT_MESSAGES,CREATED_ON) 
		 values ('".$SETTINGS."','".$VALUE."','".$created_at."')");
	
	}else{
		
			$query= mysqli_query($conn,"update DOA_ALERT_MESSAGES set MESSAGE_TYPE='".$SETTINGS."',ALERT_MESSAGES='".$VALUE."',EDITED_ON='".$created_at."' where PK_ALERT_MESSAGES='".$_GET[id]."'");	
	  
	}
	header("location:all_alert_messages.php");
}
	$SETTINGS	       = '';
	$VALUE	       = '';
if (isset($_GET['id'])) {
if($_GET['id'] == ''){
	$SETTINGS	       = '';
	$VALUE	       = '';
}
else {
$result_query = mysqli_query($conn,"SELECT * FROM DOA_ALERT_MESSAGES WHERE PK_ALERT_MESSAGES = '$_GET[id]'");
	$result=mysqli_fetch_array($result_query,MYSQLI_ASSOC);
	
	$SETTINGS       = $result['MESSAGE_TYPE'];
	$VALUE	       = $result['ALERT_MESSAGES'];
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
                                    <li class="breadcrumb-item"><a href="all_alert_messages.php">All Alert Message</a></li>
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
                                            <label class="col-md-12" for="example-text">Message Type<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="SETTINGS" name="SETTINGS" class="form-control" placeholder="enter Message Type" required data-validation-required-message="This field is required" >
                                            </div>
                                        </div>

                                        </div>
                                        <div class="col-6">
                                         <div class="form-group">
                                            <label class="col-md-12" for="example-text">Alert Messages <span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="VALUE" name="VALUE" class="form-control" placeholder="enter Alert Messages" required data-validation-required-message="This field is required">
                                            </div>
                                        </div>

                                        </div>
                                        </div>


                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                        <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_alert_messages.php'">Cancel</button>
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