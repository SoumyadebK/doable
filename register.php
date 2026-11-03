<?php require_once('global/config.php');

if(!empty($_POST)){
	
	//echo "dfd";exit;
	$roles=$_POST['roles'];
	$first_name=$_POST['first_name'];
	$Last_name=$_POST['Last_name'];
	
	
	$email=$_POST['email'];
	$password1=$_POST['pass'];
	
	$created_at=date('Y-m-d H:i:s');
	
	if ($result=mysqli_query($conn,"select PK_USER from doa_users where EMAIL_ID='$_POST[email]'"))
					{
					
                $rowcount=mysqli_num_rows($result);	
if($rowcount > 0){
	echo '<script language="javascript">alert("Email already exists. Try with another.");</script>';
	
}
else{
	
	$result_querys = mysqli_query($conn,"SELECT ACC_NUMBER FROM  doa_roles WHERE PK_ROLES = '$roles'");
	$results=mysqli_fetch_array($result_querys,MYSQLI_ASSOC);
	
	$ACC_NUMBER       = $results['ACC_NUMBER'];
	//echo $ACC_NUMBER;
	
	$result_query1 = mysqli_query($conn,"SELECT  MAX(AUTO_ID) as AUTO_ID FROM doa_users WHERE PK_ROLES = '$roles'");
	$result1=mysqli_fetch_array($result_query1,MYSQLI_ASSOC);
	
	$AUTO_ID=intval($result1['AUTO_ID']) +1 ;
	$lauto =str_pad($AUTO_ID, 4, '0', STR_PAD_LEFT);
	//echo $lauto;
	
	$ACOUNT_ID=$ACC_NUMBER . $lauto;
	
	
		$query=mysqli_query($conn,"insert into doa_users(PK_ROLES,FIRST_NAME,LAST_NAME,EMAIL_ID,PASSWORD,CREATED_ON,ACCOUNT_ID,AUTO_ID) 
		 values ('".$roles."','".$first_name."','".$Last_name."','".$email."','".$password1."','".$created_at."','".$ACOUNT_ID."','".$AUTO_ID."')");
echo "<script>
alert('Registered Successfully.');
window.location.href='login.php';
</script>";
}
 						}
					
					else{
		$result_querys = mysqli_query($conn,"SELECT ACC_NUMBER FROM  doa_roles WHERE PK_ROLES = '$roles'");
	$results=mysqli_fetch_array($result_querys,MYSQLI_ASSOC);
	
	$ACC_NUMBER       = $results['ACC_NUMBER'];
	//echo $ACC_NUMBER;
	
	$result_query1 = mysqli_query($conn,"SELECT  MAX(AUTO_ID) as AUTO_ID FROM doa_users WHERE PK_ROLES = '$roles'");
	$result1=mysqli_fetch_array($result_query1,MYSQLI_ASSOC);
	
	$AUTO_ID=intval($result1['AUTO_ID']) +1 ;
	$lauto =str_pad($AUTO_ID, 4, '0', STR_PAD_LEFT);
	//echo $lauto;
	
	$ACOUNT_ID=$ACC_NUMBER . $lauto;
	
		$query=mysqli_query($conn,"insert into doa_users(PK_ROLES,FIRST_NAME,LAST_NAME,EMAIL_ID,PASSWORD,CREATED_ON) 
		 values ('".$roles."','".$first_name."','".$Last_name."','".$email."','".$password1."','".$created_at."')");
echo "<script>
alert('Registered Successfully.');
window.location.href='login.php';
</script>";
					}
	
	
	
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    
    <title>Doable</title>
    
   <link href="assets/node_modules/register-steps/steps.css" rel="stylesheet">
    <link href="assets/dist/css/pages/register3.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/dist/css/style.min.css" rel="stylesheet">
  
</head>

<body class="skin-default card-no-border">
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
    <div class="preloader">
        <div class="loader">
            <div class="loader__figure"></div>
            <p class="loader__label">Doable</p>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->
   <section id="wrapper" class="step-register">
        <div class="register-box">
            <div class="">
 				 <div class="row">
                        <div class="col-sm-12 text-center">
                            Already have an account? <a href="login.php" class="text-info m-l-5"><b>Sign In</b></a>
                        </div>
                    </div>
                <!-- multistep form -->
                <form id="msform" action="#" method="post"  >
                    <!-- progressbar -->
                    <ul id="eliteregister">
                        <li class="active">Roles</li>
						<li>Personal Details</li>
                        <li>Account Setup</li>
                   
                    </ul>
                    <!-- fieldsets -->
					
					<fieldset>
                        <h2 class="fs-title">Roles</h2>
                       
                        <select class="form-select" required name="roles" id="roles">
                                                <option>Select Roles</option>
                                                <?php
												$result_dropdown_query = mysqli_query($conn,"select PK_ROLES,ROLES from doa_roles WHERE ACTIVE='1' order by PK_ROLES");
												while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { ?>
													<option value="<?php echo $result_dropdown['PK_ROLES'];?>"  ><?=$result_dropdown['ROLES']?></option>
												<?php
												}	
												?>
                                               
                                            </select>
                       
                        <input type="button" name="next" class="next action-button" value="Next" />
                    </fieldset>
					<fieldset>
                        <h2 class="fs-title">Personal Details</h2>
                        
                        <input type="text" name="first_name" id="first_name" placeholder="First Name" />
                        <input type="text" name="Last_name" id="Last_name" placeholder="Last Name" />
                       
                       
                        <input type="button" name="previous" class="previous action-button" value="Previous" />
						<input type="button" name="next" class="next action-button" value="Next" />
                       
                    </fieldset>
                    <fieldset>
                        <h2 class="fs-title">Create your account</h2>
                       
                        <input type="text" name="email" id="email" placeholder="Email" />
                        <input type="password" name="pass" id="pass" placeholder="Password" onkeyup="isGood(this.value)"/>
                        <input type="password" name="cpass"  id="cpass" placeholder="Confirm Password" />
						<span style="color:red">Note  : Password Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters</span>
						Password Strength: <small id="password-text"></small>
									  </br>
						 <input type="button" name="previous" class="previous action-button" value="Previous" />
                          <input type="submit"  class="submit action-button" value="Submit" />
                    </fieldset>
                    
                 
                </form>
                <div class="clear"></div>
            </div>
        </div>
    </section>
    <style>
	
.progress-bar {
  border-radius: 5px;
  height:18px !important;
}
		</style>
    <script src="assets/node_modules/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="assets/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Menu Plugin JavaScript -->
    <script src="assets/node_modules/register-steps/jquery.easing.min.js"></script>
    <script src="assets/node_modules/register-steps/register-init.js"></script>
    <script type="text/javascript">
    $(function() {
        $(".preloader").fadeOut();
    });
    $(function() {
        $('[data-bs-toggle="tooltip"]').tooltip()
    });
   
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
	
		</script>	
		<script type = "text/javascript">
   function redirectUser(){
      alert("dfhdfh");
   }
</script>
</body>

</html>