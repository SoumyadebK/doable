<?php
require_once('../global/config.php');

$type = $_GET['type'];
if ($type == 1)
    $userType = "Users";
elseif ($type == 2)
    $userType = "Customers";
elseif ($type == 3)
    $userType = "Service Providers";

if (empty($_GET['id']))
    $title = "Add ".$userType;
else
    $title = "Edit ".$userType;

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];
if(!empty($_POST)){

	//User Profile Info
	$first_name=$_POST['first_name'];
	$Last_name=$_POST['Last_name'];
	$Address=$_POST['Address'];
	$Address1=$_POST['Address1'];
	
	$Country=$_POST['Country'];
	$State=$_POST['State'];
	$City=$_POST['City'];
	$zip_code=$_POST['zip_code'];
	
	$Phone=$_POST['Phone'];
	$Fax=$_POST['Fax'];
	$email=$_POST['email'];
	$Website=$_POST['Website'];
	$Notes=$_POST['Notes'];
	
	
	$User_Id=$_POST['User_Id'];
	//$password1=$_POST['password1'];
	$roles=$_POST['roles'];
	$Gender=$_POST['Gender'];
	$DOB = $_POST['DOB'];
	
	$newfile1 ='';
	$file_dir_1 = "Uploads/";
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
	
	//User Login
	
	$roles=$_POST['roles'];
	$first_name=$_POST['first_name'];
	$Last_name=$_POST['Last_name'];
	
	
	$email=$_POST['email'];
	$password1=$_POST['pass'];
	$id = isset($_GET['id']) ? $_GET['id'] : '';
	

	
	if($id == ''){
		
			if ($result=mysqli_query($conn,"select PK_USER from DOA_USERS where EMAIL_ID='$_POST[email]'"))
					{
			                $rowcount=mysqli_num_rows($result);	
if($rowcount > 0){
	echo '<script language="javascript">alert("Email already exists. Try with another.");</script>';
	
}
					else{
				if (preg_match('/^[a-zA-Z0-9_]{8,20}$/',$User_Id ))
{	
				if ($resultre=mysqli_query($conn,"select PK_USER from DOA_USERS where USER_ID='$_POST[User_Id]'"))
					{
			                $rowcountre=mysqli_num_rows($resultre);	
if($rowcountre > 0){
	echo '<script language="javascript">alert("User Name already exists. Try with another.");</script>';
	
}	
				else {		
						
		
	
	
	//echo $PK_ACCOUNT_MASTER;exit;
	$hash = password_hash($password1, 
          PASSWORD_DEFAULT);
	//echo $hash;exit;
	$query=mysqli_query($conn,"insert into DOA_USERS(PK_ACCOUNT_MASTER,PK_ROLES,FIRST_NAME,LAST_NAME,EMAIL_ID,PASSWORD,USER_ID,CREATED_ON) 
		 values ('".$PK_ACCOUNT_MASTER."','".$roles."','".$first_name."','".$Last_name."','".$email."','".$hash."','".$User_Id."','".$created_at."')");


	$PK_USER=mysqli_insert_id($conn);
	
	
	$query_profile=mysqli_query($conn,"insert into DOA_USER_PROFILE(PK_ACCOUNT_MASTER,PK_USER,NOTES,ADDRESS,ADDRESS_1,CITY,PK_STATES,PK_COUNTRY,ZIP,PHONE,FAX,WEBSITE,CREATED_ON,PK_GENDER,DOB,USER_IMAGE) 
		 values ('".$PK_ACCOUNT_MASTER."','".$PK_USER."','".$Notes."','".$Address."','".$Address1."','".$City."','".$State."','".$Country."','".$zip_code."','".$Phone."','".$Fax."','".$Website."','".$created_at."','".$Gender."','".$DOB."','".$newfile1."')");
	
	
		echo "<script>
alert('Registered Successfully.');
window.location.href='all_users.php';
</script>";
				}
					}
		}
		else{
			echo '<script language="javascript">alert("User Name Contains Only Alphabets, Numbers and Underscore and between 8 to 20 characters.Please try with another.");</script>';
		}
					}
					
	}
	}
	
	else{
		//echo "out";exit;
			$query= mysqli_query($conn,"update DOA_ACCOUNT_MASTER set PK_ACCOUNT_TYPE='".$Account_type."',PK_BUSINESS_TYPE='".$Business_type."',BUSINESS_NAME='".$business_name."',ADDRESS='".$Account_Address."',ADDRESS_1='".$Account_Address1."',CITY='".$Account_City."',
			PK_STATES='".$Account_State."',ZIP='".$Account_zip_code."',PK_COUNTRY='".$Account_Country."',PHONE='".$Account_Phone."',FAX='".$Account_Fax."',EMAIL='".$Account_email."',WEBSITE='".$Account_Website."',EDITED_ON='".$created_at."' where PK_ACCOUNT='".$_GET[id]."'");	
	  
	
	header("location:all_users.php");
	}
	
	
	
	
	//header("location:all_users.php");
	
}


$ADDRESS 	='';
	$ADDRESS_1       = '';
	$CITY  	= '';
	$PK_STATES 	='';
	$ZIP 	='';
	$PK_COUNTRY  	= '';
	$PHONE 	='';
	$FAX 	='';
	$WEBSITE  	= '';
	$NOTES  	= '';
	$GENDER     ='';
	$DOB        = '';
	$image_upload ='';
if (isset($_GET['id'])) {
if($_GET['id'] == ''){
	$ADDRESS 	='';
	$ADDRESS_1       = '';
	$CITY  	= '';
	$PK_STATES 	='';
	$ZIP 	='';
	$PK_COUNTRY  	= '';
	$PHONE 	='';
	$FAX 	='';
	$WEBSITE  	= '';
	$NOTES  	= '';
	$GENDER    ='';
	$DOB        = '';
	$image_upload ='';
}
else {
	$result_query = mysqli_query($conn,"SELECT * FROM DOA_USER_PROFILE WHERE PK_USER_PROFILE = '$_GET[id]'");
	$result=mysqli_fetch_array($result_query,MYSQLI_ASSOC);
	
	$ADDRESS 	=$result['ADDRESS'];
	$ADDRESS_1       = $result['ADDRESS_1'];
	$CITY  	= $result['CITY'];
	$PK_STATES 	=$result['PK_STATES'];
	$ZIP 	=$result['ZIP'];
	$PK_COUNTRY  	= $result['PK_COUNTRY'];
	$PHONE 	=$result['PHONE'];
	$FAX 	=$result['FAX'];
	$WEBSITE  	= $result['WEBSITE'];
	$NOTES  	= $result['NOTES'];
	$image_upload =$result['USER_IMAGE'];
	
	$GENDER    =$result['PK_GENDER'];
	$DOB       = date("Y-m-d", strtotime($result['DOB']));
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
    <?php require_once('../includes/right_menu.php') ?>
        <div class="page-wrapper">
            <div class="container-fluid">
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
                             
                                <form class="form-material form-horizontal m-t-30" name="form1" id="form1" action="" method="post" enctype="multipart/form-data">
				 <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">New User Creation</h4>
                              
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="active"> <a class="nav-link active" data-bs-toggle="tab" href="#login" role="tab"><span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">User Login Info</span></a> </li>
                                    <li> <a class="nav-link" data-bs-toggle="tab" href="#profile" role="tab" ><span class="hidden-sm-up"><i class="ti-folder"></i></span> <span class="hidden-xs-down">User Profile</span></a> </li>
                                    
                                </ul>
                                <!-- Tab panes -->
                                <div class="tab-content tabcontent-border">
                                    <div class="tab-pane active" id="login" role="tabpanel">
                                        <div class="p-20">
                                         
									
									 <div class="row">
                                            <div class="col-md-6">
                                                
                                                    <label class="form-label">Roles</label>
                                                    <select class="form-select" required name="roles" id="roles" readonly>
                                                <?php if($type=="1") {
												    $result_dropdown_query = mysqli_query($conn,"select PK_ROLES,ROLES from DOA_ROLES WHERE ACTIVE='1' AND PK_ROLES IN (3) order by PK_ROLES");
												}
												else if($type=="2") {
													$result_dropdown_query = mysqli_query($conn,"select PK_ROLES,ROLES from DOA_ROLES WHERE ACTIVE='1' AND PK_ROLES IN (4) order by PK_ROLES");
												}
												else{
													$result_dropdown_query = mysqli_query($conn,"select PK_ROLES,ROLES from DOA_ROLES WHERE ACTIVE='1' AND PK_ROLES IN (5) order by PK_ROLES");
												}
												while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { ?>
													<option value="<?php echo $result_dropdown['PK_ROLES'];?>"  selected><?=$result_dropdown['ROLES']?></option>
												<?php
												}	
												?>
                                               
                                            </select>
                                                    
                                            </div>
											
											 <div class="col-6">
								
                                    <div class="form-group">
                                        <label class="col-md-12" for="example-text">User Name<span class="text-danger">*</span>
                                        </label>
                                        <div class="col-md-12">
                                            <input type="text" id="User_Id" name="User_Id" class="form-control" placeholder="enter User Name" required data-validation-required-message="This field is required" onkeyup="ValidateUsername()">
                                        </div>
                                    </div>
									<span id="lblError" style="color: red"></span>
									</div>
                                           
                                        </div>



				<div class="row">
                    <div class="col-6">
								
                                    <div class="form-group">
                                        <label class="col-md-12" for="example-text">First Name<span class="text-danger">*</span>
                                        </label>
                                        <div class="col-md-12">
                                            <input type="text" id="first_name" name="first_name" class="form-control" placeholder="enter First Name" required data-validation-required-message="This field is required" >
                                        </div>
                                    </div>
									
									</div>
									<div class="col-6">
									 <div class="form-group">
                                        <label class="col-md-12" for="example-text">Last Name
                                        </label>
                                        <div class="col-md-12">
                                            <input type="text" id="Last_name" name="Last_name" class="form-control" placeholder="enter Last Name" >
                                        </div>
                                    </div>
									
									</div>
									</div>
									
									<div class="row">
                    <div class="col-6">
								
                                    <div class="form-group">
                                        <label class="col-md-12" for="example-text">Email<span class="text-danger">*</span>
                                        </label>
                                        <div class="col-md-12">
                                            <input type="email" id="email" name="email" class="form-control" placeholder="enter Email Adress" required data-validation-required-message="This field is required" >
                                        </div>
                                    </div>
									
									</div>
									
									</div>	
									
									<div class="row">
                    <div class="col-6">
								
                                    <div class="form-group">
                                        <label class="col-md-12" for="example-text">Password<span class="text-danger">*</span>
                                        </label>
                                        <div class="col-md-12">
                                           <input type="password" class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="pass" id="pass" required data-validation-required-message="This field is required"  onkeyup="isGood(this.value)">
                                        </div>
                                    </div>
									
									</div>
									<div class="col-6">
									 <div class="form-group">
                                        <label class="col-md-12" for="example-text">Confirm Password<span class="text-danger">*</span>
                                        </label>
                                        <div class="col-md-12">
                                             <input type="password" class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="cpass" id="cpass" required data-validation-required-message="This field is required"  onkeyup="isGood(this.value)">
                                        </div>
                                    </div>
									
									</div>
									</div>
									
                                 <div class="row">
									<div class="col-12">
									<span style="color:red">Note  : Password Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters</span>
									</div>
									</div>
									<div class="row">
									<div class="col-2">
									Password Strength:  
									</div>
									<div class="col-3">
									<small id="password-text"></small></div>
</div>
									
                                        </div>
                                    </div>
									
									
									
                                    <div class="tab-pane  p-20" id="profile" role="tabpanel">
									
									<div class="p-20">
									
									
									 <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group has-success">
                                                    <label class="form-label">Gender</label>
                                                    <select class="form-control form-select" id="Gender" name="Gender">
                                                        <option value="1" <?php if($GENDER == "1") echo 'selected = "selected"';?>>Male</option>
                                                        <option value="2" <?php if($GENDER == "2") echo 'selected = "selected"';?>>Female</option>
                                                    </select>
                                                    <small class="form-control-feedback"> Select your gender </small> </div>
                                            </div>
                                            <!--/span-->
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Date of Birth</label>
                                                    <input type="date" class="form-control" placeholder="dd/mm/yyyy" id="DOB" name="DOB" value="<?php echo $DOB; ?>">
                                                </div>
                                            </div>
                                            <!--/span-->
                                        </div>
										
										 
								<div class="row">
                    <div class="col-6">
								
                                    <div class="form-group">
                                        <label class="col-md-12" for="example-text">Address
                                        </label>
                                        <div class="col-md-12">
										<input type="text" id="Address" name="Address" class="form-control" placeholder="enter Address" value="<?php echo $ADDRESS?>">
                                           
                                        </div>
                                    </div>
									
									</div>
									<div class="col-6">
									 <div class="form-group">
                                        <label class="col-md-12" for="example-text">Address 1
                                        </label>
                                        <div class="col-md-12">
										<input type="text" id="Address1" name="Address1" class="form-control" placeholder="enter Address" value="<?php echo $ADDRESS_1?>">
                                           
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
                                        <label class="col-md-12" for="example-text">Zip Code</span>
                                        </label>
                                        <div class="col-md-12">
                                            <input type="text" id="zip_code" name="zip_code" class="form-control" placeholder="enter Zip Code" value="<?php echo $ZIP?>">
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
                                        <label class="col-md-12" for="example-text">Fax
                                        </label>
                                        <div class="col-md-12">
                                            <input type="text" id="Fax" name="Fax" class="form-control" placeholder="enter Fax" value="<?php echo $FAX;?>">
                                        </div>
                                    </div>
									
									</div>
									</div>
									
									
									<div class="row">
                   
									<div class="col-6">
									 <div class="form-group">
                                        <label class="col-md-12" for="example-text">Website
                                        </label>
                                        <div class="col-md-12">
                                            <input type="text" id="Website" name="Website" class="form-control" placeholder="enter Website" value="<?php echo $WEBSITE?>">
                                        </div>
                                    </div>
									
									</div>
									
									
									<div class="col-6">
									 <div class="form-group">
                                        <label class="col-md-12" for="example-text">Image Upload
                                        </label>
                                        <div class="col-md-12">
                                              <input type="file" name="image_upload" id="image_upload" class="form-control" > </div>
                                        </div>
                                    </div>
									
									</div>
									<div class="form-group">
								<?php if($image_upload!=''){?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $image_upload;?>" data-fancybox-group="gallery"><img src = "<?php echo $image_upload;?>" style="width:120px; height:120px" /></a></div><?php } ?>
                                    </div>
									
									
									<div class="form-group">
                                        <label class="col-md-12">Remarks</label>
                                        <div class="col-md-12">
                                            <textarea class="form-control" rows="3" id="Notes" name="Notes"><?php echo $NOTES?></textarea>
                                        </div>
                                    </div>
                                 
								
								  <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white ">Submit</button>
                                    <button type="button" onclick="window.location.href='all_users.php'" class="btn btn-inverse waves-effect waves-light">Cancel</button>
									</div>
									</div>
                                    
                                </div>
								<div style="height:20px"; ></div>
								 <div class="row">
								 <div class="col-md-9"></div>
								 <div class="col-md-3">
								
								</div>
								</div>
                            </div>
                        </div>
						  
                    </div>
                   
                </div>
				</form>
                            </div>
                        </div>
                    </div>
                </div>
				
			
			</div>
		</div>
		<style>
	
.progress-bar {
  border-radius: 5px;
  height:18px !important;
}
		</style>
        <?php require_once('../includes/footer.php');?>
		<script>
		 $(document).ready(function() { 
			fetch_state(<?php  echo $PK_COUNTRY; ?>);
			
		 });
			
		function fetch_state(PK_COUNTRY){
		
					jQuery(document).ready(function($) {
						
						var data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES=<?=$PK_STATES;?>"; 
				
					var value = $.ajax({
							url: "State.php",	
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
		<script>
		function isGood(password) {
			//alert(password);
      var password_strength = document.getElementById("password-text");

      //TextBox left blank.
      if (password.length == 0) {
        password_strength.innerHTML = "";
        return;
      }

      //Regular Expressions.
      var regex = new Array();
      regex.push("[A-Z]"); //Uppercase Alphabet.
      regex.push("[a-z]"); //Lowercase Alphabet.
      regex.push("[0-9]"); //Digit.
      regex.push("[$@$!%*#?&]"); //Special Character.

      var passed = 0;

      //Validate for each Regular Expression.
      for (var i = 0; i < regex.length; i++) {
        if (new RegExp(regex[i]).test(password)) {
          passed++;
        }
      }

      //Display status.
      var strength = "";
      switch (passed) {
        case 0:
        case 1:
        case 2:
          strength = "<small class='progress-bar bg-danger' style='width: 50%'>Weak</small>";
          break;
        case 3:
          strength = "<small class='progress-bar bg-warning' style='width: 60%'>Medium</small>";
          break;
        case 4:
          strength = "<small class='progress-bar bg-success' style='width: 100%'>Strong</small>";
          break;

      }
	 // alert(strength);
      password_strength.innerHTML = strength;

    }
	
	function ValidateUsername() {
        var username = document.getElementById("User_Id").value;
        var lblError = document.getElementById("lblError");
        lblError.innerHTML = "";
        var expr = /^[a-zA-Z0-9_]{8,20}$/;
        if (!expr.test(username)) {
            lblError.innerHTML = "Only Alphabets, Numbers and Underscore and between 8 to 20 characters.";
        }
		else{
			lblError.innerHTML = "";
		}
    }
		</script>	
		
	
		
		
	</body>
</html>