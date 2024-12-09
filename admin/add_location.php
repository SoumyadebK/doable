<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Location";
else
    $title = "Edit Location";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
	
	$Location= $_POST['Location'];
	$Address=$_POST['Address'];
	$Address1=$_POST['Address1'];
	
	$Country=$_POST['Country'];
	$State=$_POST['State'];
	$City=$_POST['City'];
	$zip_code=$_POST['zip_code'];
	
	$Phone=$_POST['Phone'];
	$email=$_POST['email'];
	$created_at=date('Y-m-d H:i:s');
	$newfile1 ='';
	$file_dir_1 = "uploads/";
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
		
		$query=mysqli_query($conn,"insert into DOA_LOCATION_MASTER(PK_ACCOUNT_MASTER,CREATED_ON) 
		 values ('".$_SESSION['PK_ACCOUNT_MASTER']."','".$created_at."')");
	
	$PK_LOCATION_MASTER=mysqli_insert_id($conn);
		
		$query=mysqli_query($conn,"insert into DOA_LOCATION_DETAILS(PK_LOCATION_MASTER,LOCATION_NAME,ADDRESS,ADDRESS_1,CITY,PK_STATES,PK_COUNTRY,ZIP_CODE,PHONE,EMAIL,CREATED_ON,IMAGE_PATH) 
		 values ('".$PK_LOCATION_MASTER."','".$Location."','".$Address."','".$Address1."','".$City."','".$State."','".$Country."','".$zip_code."','".$Phone."','".$email."','".$created_at."','".$newfile1."')");
	
	}else{
		if($newfile1!=''){
			$query=mysqli_query($conn,"update DOA_LOCATION_DETAILS set IMAGE_PATH='".$newfile1."' where PK_LOCATION_DETAILS='".$_GET[id]."'");
		}
			$query= mysqli_query($conn,"update DOA_LOCATION_DETAILS set LOCATION_NAME='".$_POST['Location']."',ADDRESS='".$Address."',ADDRESS_1='".$Address1."',CITY='".$City."',
			PK_STATES='".$State."',ZIP_CODE='".$zip_code."',PK_COUNTRY='".$Country."',PHONE='".$Phone."',EMAIL='".$email."',EDITED_ON='".$created_at."' where PK_LOCATION_DETAILS='".$_GET[id]."'");	
	  
	}
	header("location:all_locations.php");
}
	$LOCATION_NAME	       = '';
	$ADDRESS 	='';
	$ADDRESS_1       = '';
	$CITY  	= '';
	$PK_STATES 	='';
	$ZIP 	='';
	$PK_COUNTRY  	= '';
	$PHONE 	='';
	$EMAIL       = '';
	$image_upload ='';
if (isset($_GET['id'])) {
if($_GET['id'] == ''){
	$LOCATION_NAME	       = '';
	$ADDRESS 	='';
	$ADDRESS_1       = '';
	$CITY  	= '';
	$PK_STATES 	='';
	$ZIP 	='';
	$PK_COUNTRY  	= '';
	$PHONE 	='';
	$EMAIL       = '';
	$image_upload ='';
}
else {
$result_query = mysqli_query($conn,"SELECT * FROM DOA_LOCATION_DETAILS WHERE PK_LOCATION_DETAILS = '$_GET[id]'");
	$result=mysqli_fetch_array($result_query,MYSQLI_ASSOC);
	
	$LOCATION_NAME       = $result['LOCATION_NAME'];
	$ADDRESS 	=$result['ADDRESS'];
	$ADDRESS_1       = $result['ADDRESS_1'];
	$CITY  	= $result['CITY'];
	$PK_STATES 	=$result['PK_STATES'];
	$ZIP 	=$result['ZIP_CODE'];
	$PK_COUNTRY  	= $result['PK_COUNTRY'];
	$PHONE 	=$result['PHONE'];
	$EMAIL       = $result['EMAIL'];
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
            <?php require_once('../includes/left_menu.php') ?>
                <div class="page-wrapper">
                    <div class="container-fluid">
                        <div class="row page-titles">
                            <div class="col-md-5 align-self-center">
                                <h4 class="text-themecolor">Add Location</h4>
                            </div>
                            <div class="col-md-7 align-self-center text-end">
                                <div class="d-flex justify-content-end align-items-center">
                                    <ol class="breadcrumb justify-content-end">
                                        <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                                        <li class="breadcrumb-item active">Add Location</li>
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
                                                        <label class="col-md-12" for="example-text">Location<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="Location" name="Location" class="form-control" placeholder="enter Location" required data-validation-required-message="This field is required" value="<?php echo $LOCATION_NAME?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Location Code<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="Location" name="Location" class="form-control" placeholder="Enter Location Code" required data-validation-required-message="This field is required" value="<?php echo $LOCATION_NAME?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                            <div class="col-6">

                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Address
                                                </label>
                                                <div class="col-md-12">
                                                    <textarea class="form-control" rows="3" id="Address" name="Address"><?php echo $ADDRESS?></textarea>
                                                </div>
                                            </div>

                                            </div>
                                            <div class="col-6">
                                             <div class="form-group">
                                                <label class="col-md-12" for="example-text">Apt/Ste
                                                </label>
                                                <div class="col-md-12">
                                                    <textarea class="form-control" rows="3" id="Address1" name="Address1" ><?php echo $ADDRESS_1?></textarea>
                                                </div>
                                            </div>

                                            </div>
                                            </div>

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
                                                        $result_dropdown_query = mysqli_query($conn,"select PK_COUNTRY,COUNTRY_NAME from DOA_COUNTRY WHERE ACTIVE='1' order by PK_COUNTRY");
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

                                            <div class="col-6">
                                             <div class="form-group">
                                                <label class="col-md-12" for="example-text">State<span class="text-danger">*</span>
                                                </label>
                                                <div class="col-md-12">
                                                    <div class="col-sm-12">

                                                      <div id="State_div"></div>

                                                </div>
                                                </div>
                                            </div>

                                            </div>
                                            </div>

                                            <div class="row">

                                            <div class="col-6">

                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">City</span>
                                                </label>
                                                <div class="col-md-12">
                                                    <input type="text" id="City" name="City" class="form-control" placeholder="enter your city" value="<?php echo $CITY?>">
                                                </div>
                                            </div>

                                            </div>
                            <div class="col-6">

                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Postal / Zip Code</span>
                                                </label>
                                                <div class="col-md-12">
                                                    <input type="text" id="zip_code" name="zip_code" class="form-control" placeholder="enter Postal / Zip Code" value="<?php echo $ZIP?>">
                                                </div>
                                            </div>

                                            </div>

                                            </div>

                                            <div class="row">
                            <div class="col-6">

                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Phone
                                                </label>
                                                <div class="col-md-12">
                                                    <input type="text" id="Phone" name="Phone" class="form-control" placeholder="enter Phone No." value="<?php echo $PHONE?>">
                                                </div>
                                            </div>

                                            </div>
                                            <div class="col-6">

                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Email<span class="text-danger">*</span>
                                                </label>
                                                <div class="col-md-12">
                                                    <input type="email" id="email" name="email" class="form-control" placeholder="enter Email Adress" required data-validation-required-message="This field is required" value="<?php echo $EMAIL?>">
                                                </div>
                                            </div>

                                            </div>
                                            </div>
                                            <div class="form-group">
                                                <h5>File Input Field <span class="text-danger"></span></h5>
                                                <div class="controls">
                                                    <input type="file" name="image_upload" id="image_upload" class="form-control"> </div>
                                            </div>
                                            <div class="form-group">
                                        <?php if($image_upload!=''){?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $image_upload;?>" data-fancybox-group="gallery"><img src = "<?php echo $image_upload;?>" style="width:120px; height:120px" /></a></div><?php } ?>
                                            </div>


                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                            <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_locations.php'">Cancel</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php require_once('../includes/footer.php');?>
		
			<script>
		 $(document).ready(function() { 
			fetch_state(<?php  echo $PK_COUNTRY; ?>);
		 });
			
		
		function fetch_state(PK_COUNTRY){
			
					jQuery(document).ready(function($) {
						var data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES=<?=$PK_STATES;?>"; 
						
						var value = $.ajax({
							url: "ajax/state.php",
							type: "POST",
							data: data,		
							async: false,
							cache :false,
							success: function (result) {
								document.getElementById('State_div').innerHTML = result;								
						
						}		
					}).responseText;
				});
			}
		</script>
	</body>
</html>