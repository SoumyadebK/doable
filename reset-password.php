<?
require_once('global/config.php');

$id = $_GET['id'];
$decodeTime = base64_decode($id);
$new = explode('_', $decodeTime);
$sentTime = $new[1];
$currentTime = time();
$timeLimit = $sentTime + 240;
$PK_USER = $new[0];

$msg = '';
$success_msg = '';
$expire = '';
$FUNCTION_NAME = isset($_POST['FUNCTION_NAME']) ? $_POST['FUNCTION_NAME'] : '';

if ($currentTime <= $timeLimit) {
    if ($FUNCTION_NAME == 'newPasswordFunction') {
        $PASSWORD = trim($_POST['PASSWORD']);
        $CPASSWORD = trim($_POST['CPASSWORD']);
        if($PASSWORD==$CPASSWORD){
            $result = $db->Execute("SELECT * FROM `DOA_USERS` WHERE PASSWORD = '$PASSWORD'");
            if ($result->RecordCount() == 0) {
                $USER_DATA['PASSWORD'] = password_hash($PASSWORD, PASSWORD_DEFAULT);
                db_perform('DOA_USERS', $USER_DATA, 'update', "PK_USER =  '$PK_USER'");
                $success_msg = "Your password is changed";
            } else {
                $msg = "Your password is not changed";
            }
        }
    }
} else {
    $expire = "Oops! Your link is expired";
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

    <title>Doable Login</title>

    <!-- page css -->
    <link href="assets/dist/css/pages/login-register-lock.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/dist/css/style.min.css" rel="stylesheet">

</head>

<body class="skin-default card-no-border">
<div class="preloader">
    <div class="loader">
        <div class="loader__figure"></div>
        <p class="loader__label">Doable</p>
    </div>
</div>
<section id="wrapper">

    <div class="login-register" style="background-image:url(assets/images/background/login_image.jpg);">
        <div>
            <img src="assets/images/background/doable_logo.png" style="margin-left:5%; margin-top: -150px; height: 80px; width: auto;">
        </div>
        <div class="login-box card">
            <div class="card-body">
                <form class="form-horizontal form-material" action="" method="post">
                    <input type="hidden" name="FUNCTION_NAME" value="newPasswordFunction">
                    <?php if ($msg) {?>
                        <div class="alert alert-danger">
                            <strong><?=$msg;?></strong>
                        </div>
                    <?php } ?>
                    <?php if ($success_msg) {?>
                        <div class="alert alert-success">
                            <strong><?=$success_msg;?></strong>
                        </div>
                    <?php } ?>
                    <?php if ($expire) {?>
                        <div class="alert alert-danger">
                            <h3><?=$expire;?></h3>
                        </div>
                    <?php } ?>


                    <?php if (!$expire) {?>
                        <h3 class="text-center m-b-20">Enter New Password</h3>
                        <div>
                            <img src="assets/images/background/doable_logo.png" style="margin-left: 33%; height: 60px; width: auto;">
                        </div>
                    <div class="form-group ">
                        <div class="col-xs-12">
                            <input class="form-control" type="text" required="" placeholder="New Password" id="PASSWORD" name="PASSWORD">
                        </div>
                        <div class="col-xs-12">
                            <input class="form-control" type="text" required="" placeholder="Confirm Password" id="CPASSWORD" name="CPASSWORD">
                        </div>
                    </div>
                    <div class="form-group text-center">
                        <div class="col-xs-12 p-b-20">
                            <button class="btn w-100 btn-lg btn-info btn-rounded text-white" type="submit">Reset</button>
                        </div>
                    </div>
                    <?php } ?>
                    <div class="form-group m-b-0">
                        <div class="col-sm-12 text-center">
                            <a href="login.php" id="to-login" class="text-info m-l-5"><b> Go To Login Page </b></a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<!-- ============================================================== -->
<!-- End Wrapper -->
<!-- ============================================================== -->

<!-- ============================================================== -->
<!-- All Jquery -->
<!-- ============================================================== -->
<script src="assets/node_modules/jquery/dist/jquery.min.js"></script>
<!-- Bootstrap tether Core JavaScript -->
<script src="assets/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<!--Custom JavaScript -->
<script type="text/javascript">
    $(function() {
        $(".preloader").fadeOut();
    });
    $(function() {
        $('[data-bs-toggle="tooltip"]').tooltip()
    });
    $('#to-recover').on("click", function() {
        $("#loginform").slideUp();
        $("#recoverform").fadeIn();
    });
    $('#to-login').on("click", function() {
        $("#loginform").fadeIn();
        $("#recoverform").slideUp();
    });
</script>

</body>

</html>