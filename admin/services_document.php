<?php
require_once('../global/config.php');
$title = "Add Service Document";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
	
	$service_name= $_POST['service_name'];
	$desc=$_POST['desc'];
	$Duration=$_POST['Duration'];
	
	$Price=$_POST['Price'];
	
	$created_at=date('Y-m-d H:i:s');
	
	$file_dir_1 = "../uploads/";
		if (trim($_FILES['image_upload']['name'])!=""){
 			$extn = explode(".",$_FILES['image_upload']['name']);
 			$rand_string = rand(10000,99999);
			$file11=$extn[0].$rand_string.".".$extn[1];
 			$type=$_FILES['image_upload']['type'];
 			//if($type=="image/gif" ||$type=="image/jpeg" || $type=="image/pjpeg" || $type=="image/png" ||$type=="image/jpg "){  
  				$newfile1 = $file_dir_1.$file11;
  				move_uploaded_file($_FILES['image_upload']['tmp_name'],$newfile1);
 			//}
			
	}
	


	if($_GET['id'] == ''){
		$query=mysqli_query($conn,"insert into DOA_SERVICE_MASTER(SERVICE_NAME,DESCRIPTION,DURATION,PRICE,CREATED_ON,IMAGE_PATH) 
		 values ('".$service_name."','".$desc."','".$Duration."','".$Price."','".$created_at."','".$newfile1."')");
	
	}else{
		if($newfile1!=''){
			$query=mysqli_query($conn,"update DOA_SERVICE_MASTER set IMAGE_PATH='".$newfile1."' where PK_SERVICE_MASTER='".$_GET[id]."'");
		}
			$query= mysqli_query($conn,"update DOA_SERVICE_MASTER set SERVICE_NAME='".$service_name."',DESCRIPTION='".$desc."',DURATION='".$Duration."',PRICE='".$Price."',EDITED_ON='".$created_at."' where PK_SERVICE_MASTER='".$_GET[id]."'");	
	  
	}
	header("location:All_Services.php");
}
	$SERVICE_NAME	       = '';
	$DESCRIPTION 	='';
	$DURATION       = '';
	$PRICE  	= '';
	$image_upload ='';
if (isset($_GET['id'])) {
if($_GET['id'] == ''){
	$SERVICE_NAME	       = '';
	$DESCRIPTION 	='';
	$DURATION       = '';
	$PRICE  	= '';
	$image_upload ='';
}
else {
$result_query = mysqli_query($conn,"SELECT * FROM DOA_SERVICE_MASTER WHERE PK_SERVICE_MASTER = '$_GET[id]'");
	$result=mysqli_fetch_array($result_query,MYSQLI_ASSOC);
	
	$SERVICE_NAME       = $result['SERVICE_NAME'];
	$DESCRIPTION 	=$result['DESCRIPTION'];
	$DURATION       = $result['DURATION'];
	$PRICE  	= $result['PRICE'];
	$image_upload =$result['IMAGE_PATH'];
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
                                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                                <li class="breadcrumb-item active"><?=$title?></li>
                            </ol>
                            
                        </div>
                    </div>
                </div>
				
				 <form class="form-material form-horizontal m-t-30" name="form1" id="form1" action="" method="post" enctype="multipart/form-data">
				 <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Services Details</h4>
                              
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item"> <a class="nav-link active" data-bs-toggle="tab" href="#home" role="tab"><span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">Services</span></a> </li>
                                    <li class="nav-item"> <a class="nav-link" data-bs-toggle="tab" href="#profile" role="tab"><span class="hidden-sm-up"><i class="ti-folder"></i></span> <span class="hidden-xs-down">Documents</span></a> </li>
                                    
                                </ul>
                                <!-- Tab panes -->
                                <div class="tab-content tabcontent-border">
                                    <div class="tab-pane active" id="home" role="tabpanel">
                                        <div class="p-20">
                                            <div class="form-group">
                                        <label class="col-md-12" for="example-text">Service Name<span class="text-danger">*</span>
                                        </label>
                                        <div class="col-md-12">
                                            <input type="text" id="service_name" name="service_name" class="form-control" placeholder="enter Service name" required data-validation-required-message="This field is required" value="<?php echo $SERVICE_NAME?>">
                                        </div>
                                    </div>
								
								<div class="form-group">
                                        <label class="col-md-12">Description</label>
                                        <div class="col-md-12">
                                            <textarea class="form-control" rows="3" id="desc" name="desc"><?php echo $DESCRIPTION?></textarea>
                                        </div>
                                    </div>
								<div class="row">
                    <div class="col-6">
								
                                    <div class="form-group">
                                        <label class="col-md-12" for="example-text">Duration<span class="text-danger">*</span>
                                        </label>
                                        <div class="col-md-12">
                                            <input type="text" id="Duration" name="Duration" class="form-control" placeholder="enter Duration" required data-validation-required-message="This field is required" value="<?php echo $DURATION?>">
                                        </div>
                                    </div>
									
									</div>
									<div class="col-6">
									 <div class="form-group">
                                        <label class="col-md-12" for="example-text">Price<span class="text-danger">*</span>
                                        </label>
                                        <div class="col-md-12" >
										<div class="input-group">
										<span class="input-group-text"><?=$currency?></span>
                                            <input type="number" id="Price" name="Price" class="form-control" placeholder="enter Price" required data-validation-required-message="This field is required" value="<?php echo $PRICE?>">
											</div>
                                        </div>
                                    </div>
									
									</div>
									</div>
									
									
									
                                        </div>
                                    </div>
                                    <div class="tab-pane  p-20" id="profile" role="tabpanel">
									
									
									<div class="row">
                    <div class="col-6">
								
                                    <div class="form-group">
                                        <label class="col-md-12" for="example-text">Location<span class="text-danger"></span>
                                        </label>
                                        <div class="col-md-12">
                                            <select class="form-select" required name="Account_type" id="Account_type">
                                                <option>Select Location</option>
                                                <?php
												$result_dropdown_query = mysqli_query($conn,"SELECT `PK_LOCATION`, `LOCATION_NAME` FROM `DOA_LOCATION` WHERE `ACTIVE` AND `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
												while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { ?>
													<option value="<?php echo $result_dropdown['PK_LOCATION'];?>"  ><?=$result_dropdown['LOCATION_NAME']?></option>
												<?php
												}	
												?>
                                               
                                            </select>
                                        </div>
                                    </div>
									
									</div>
									 <div class="col-6">
									   <label class="col-md-12" for="example-text">File Input Field<span class="text-danger"></span>
                                        </label>
									  <div class="col-md-12">
									  <input type="file" name="image_upload" id="image_upload" class="form-control" >
									  </div>
									 </div>
									
									</div>
									
									
									
                                  <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white ">Submit</button>
                                    <button type="button" onclick="window.location.href='All_Services.php'" class="btn btn-inverse waves-effect waves-light">Cancel</button>
									
									</div>
                                    
                                </div>
								
                            </div>
                        </div>
						  
                    </div>
                   
                </div>
				</form>
				
				
			
			</div>
		</div>

        <?php require_once('../includes/footer.php');?>
	</body>
</html>