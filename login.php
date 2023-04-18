<?
require_once('global/config.php');
$msg = '';
if(!empty($_POST)){
    $USER_ID = trim($_POST['USER_ID']);
    $PASSWORD = trim($_POST['PASSWORD']);

    $result = $db->Execute("SELECT DOA_USERS.*, DOA_ACCOUNT_MASTER.ACTIVE AS ACCOUNT_ACTIVE FROM `DOA_USERS` LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_USERS.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER WHERE DOA_USERS.USER_ID = '$USER_ID'");
    if($result->RecordCount() > 0) {
        if (($result->fields['ACCOUNT_ACTIVE'] == 1 || $result->fields['ACCOUNT_ACTIVE'] == '' || $result->fields['ACCOUNT_ACTIVE'] == NULL) && $result->fields['ACTIVE'] == 1 && $result->fields['CREATE_LOGIN'] == 1) {
            if (password_verify($PASSWORD, $result->fields['PASSWORD'])) {
                $_SESSION['PK_USER'] = $result->fields['PK_USER'];
                $_SESSION['PK_ACCOUNT_MASTER'] = $result->fields['PK_ACCOUNT_MASTER'];
                $_SESSION['PK_ROLES'] = $result->fields['PK_ROLES'];
                $_SESSION['FIRST_NAME'] = $result->fields['FIRST_NAME'];
                $_SESSION['LAST_NAME'] = $result->fields['LAST_NAME'];
                $_SESSION['ACCESS_TOKEN'] = $result->fields['ACCESS_TOKEN'];
                $_SESSION['TICKET_SYSTEM_ACCESS'] = $result->fields['TICKET_SYSTEM_ACCESS'];

                if ($_SESSION['PK_ROLES'] == 1) {
                    header("location: super_admin/all_accounts.php");
                } elseif ($_SESSION['PK_ROLES'] == 2) {
                    header("location: admin/all_schedules.php");
                } elseif ($_SESSION['PK_ROLES'] == 4) {
                    $account = $db->Execute("SELECT * FROM DOA_USER_MASTER WHERE PK_USER = ".$result->fields['PK_USER']." LIMIT 1");
                    $_SESSION['PK_ACCOUNT_MASTER'] = $account->fields['PK_ACCOUNT_MASTER'];
                    header("location: customer/all_schedules.php");
                } elseif ($_SESSION['PK_ROLES'] == 5) {
                    header("location: service_provider/all_schedules.php");
                }
            } else {
                $msg = "Invalid Password";
            }
        }else{
            $msg = "User is Inactive";
        }
    } else {
        $msg = "Invalid Username";
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

                <form class="form-horizontal form-material" id="loginform" action="" method="post">
                    <?php if ($msg) {?>
                    <div class="alert alert-danger">
                        <strong><?=$msg;?></strong>
                    </div>
                    <?php } ?>
                    <h3 class="text-center m-b-20">Sign In</h3>
                    <div>
                        <img src="assets/images/background/doable_logo.png" style="margin-left: 33%; height: 60px; width: auto;">
                    </div>

                    <div class="form-group ">
                        <div class="col-xs-12">
                            <input class="form-control" type="text" required="" placeholder="Username" id="USER_ID" name="USER_ID">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-12">
                            <input class="form-control" type="password" required="" placeholder="Password" id="PASSWORD" name="PASSWORD">
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-12">
                            <div class="d-flex no-block align-items-center">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="customCheck1">
                                    <label class="form-check-label" for="customCheck1">Remember me</label>
                                </div>
                                <div class="ms-auto">
                                    <a href="forgot-password.php" id="to-recover" class="text-muted"><i class="fas fa-lock m-r-5"></i> Forgot password?</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group text-center">
                        <div class="col-xs-12 p-b-20">
                            <button class="btn w-100 btn-lg btn-info btn-rounded text-white" type="submit">Log In</button>
                        </div>
                    </div>

                    <!--<div class="form-group m-b-0">
                        <div class="col-sm-12 text-center">
                            Don't have an account? <a href="register.php" class="text-info m-l-5"><b>Sign Up</b></a>
                        </div>
                    </div>-->
                </form>
                <form class="form-horizontal" id="recoverform" action="forgot-password.php">
                    <div class="form-group ">
                        <div class="col-xs-12">
                            <h3>Recover Password</h3>
                            <p class="text-muted">Enter your Email and instructions will be sent to you! </p>
                        </div>
                    </div>
                    <div class="form-group ">
                        <div class="col-xs-12">
                            <input class="form-control" type="text" required="" name="email" placeholder="Email"> </div>
                    </div>
                    <div class="form-group text-center m-t-20">
                        <div class="col-xs-12">
                            <button class="btn btn-primary btn-lg w-100 text-uppercase waves-effect waves-light" type="submit" name="submit_email">Reset</button>
                        </div>
                    </div>

                    <div class="form-group m-b-0">
                        <div class="col-sm-12 text-center">
                            <a href="javascript:void(0)" id="to-login" class="text-info m-l-5"><b> Go To Login Page </b></a>
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