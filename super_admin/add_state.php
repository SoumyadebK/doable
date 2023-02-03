<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add State";
else
    $title = "Edit State";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
	
	$STATE_NAME= $_POST['STATE_NAME'];
	$STATE_CODE= $_POST['STATE_CODE'];
	$Country=$_POST['Country'];
	$created_at=date('Y-m-d H:i:s');
	if($_GET['id'] == ''){
		$query=mysqli_query($conn,"insert into DOA_STATES(PK_COUNTRY,STATE_NAME,STATE_CODE,CREATED_ON) 
		 values ('".$Country."','".$STATE_NAME."','".$STATE_CODE."','".$created_at."')");
	
	}else{
		
			$query= mysqli_query($conn,"update DOA_STATES set PK_COUNTRY='".$Country."',STATE_NAME='".$STATE_NAME."',STATE_CODE='".$STATE_CODE."',EDITED_ON='".$created_at."' where PK_STATES='".$_GET[id]."'");	
	  
	}
	header("location:all_states.php");
}
	$STATE_NAME	       = '';
	$STATE_CODE	       = '';
	$PK_COUNTRY	       = '';
if (isset($_GET['id'])) {
if($_GET['id'] == ''){
	$STATE_NAME	       = '';
	$STATE_CODE	       = '';
	$PK_COUNTRY	       = '';
}
else {
$result_query = mysqli_query($conn,"SELECT * FROM DOA_STATES WHERE PK_STATES = '$_GET[id]'");
	$result=mysqli_fetch_array($result_query,MYSQLI_ASSOC);
	
	$STATE_NAME       = $result['STATE_NAME'];
	$STATE_CODE	       = $result['STATE_CODE'];
	$PK_COUNTRY	       = $result['PK_COUNTRY'];
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
                <div class="container-fluid">
                    <div class="row page-titles">
                        <div class="col-md-5 align-self-center">
                            <h4 class="text-themecolor"><?=$title?></h4>
                        </div>
                        <div class="col-md-7 align-self-center text-end">
                            <div class="d-flex justify-content-end align-items-center">
                                <ol class="breadcrumb justify-content-end">
                                    <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                    <li class="breadcrumb-item"><a href="all_states.php">All States</a></li>
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
                                            <label class="col-md-12" for="example-text">Country<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <div class="col-sm-12">
                                                <select class="form-select" required name="Country" id="Country" onChange="fetch_state(this.value)">
                                                    <option>Select Country</option>
                                                    <?php
                                                    $result_dropdown_query = mysqli_query($conn,"select PK_COUNTRY,COUNTRY_NAME from doa_country WHERE ACTIVE='1' order by PK_COUNTRY");
                                                    while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { ?>
                                                        <option value="<?php echo $result_dropdown['PK_COUNTRY'];?>" <?php if($result_dropdown['PK_COUNTRY'] == $PK_COUNTRY) echo 'selected = "selected"';?> ><?=$result_dropdown['COUNTRY_NAME']?></option>
                                                    <?php
                                                    }
                                                    ?>

                                                </select>
                                            </div>
                                            </div>
                                        </div>

                                        </div>
                                    </div>

                                    <div class="row">
                        <div class="col-6">

                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">State Code<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                               <input type="text" id="STATE_CODE" name="STATE_CODE" class="form-control" placeholder="enter State Code" required data-validation-required-message="This field is required" value="<?php echo $STATE_CODE?>">
                                            </div>
                                        </div>

                                        </div>
                                        <div class="col-6">
                                         <div class="form-group">
                                            <label class="col-md-12" for="example-text">State Name<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="STATE_NAME" name="STATE_NAME" class="form-control" placeholder="enter State Name" required data-validation-required-message="This field is required" value="<?php echo $STATE_NAME?>">
                                            </div>
                                        </div>

                                        </div>
                                        </div>

                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                        <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_states.php'">Cancel</button>
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