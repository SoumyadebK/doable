<?
require_once('global/config.php');
$msg = '';
$success_msg = '';
$FUNCTION_NAME = isset($_POST['FUNCTION_NAME']) ? $_POST['FUNCTION_NAME'] : '';

if ($FUNCTION_NAME == 'resetPasswordFunction') {
    $email = $_POST['EMAIL'];
    $result = $db->Execute("SELECT * FROM `DOA_USERS` WHERE EMAIL_ID = '$email'");
    if ($result->RecordCount() > 0) {
        $to= $result->fields['EMAIL_ID'];
        $time = base64_encode($result->fields['PK_USER'].'_'.time());
        $link = $http_path.'reset-password.php?cmVzZXQ='.$time;
        //pre_r($link);
        $PK_USER = $result->fields['PK_USER'];
        $details = $db->Execute("SELECT DOA_EMAIL_ACCOUNT.USER_NAME, DOA_EMAIL_ACCOUNT.PASSWORD, DOA_EMAIL_ACCOUNT.HOST, DOA_EMAIL_ACCOUNT.PORT, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME FROM DOA_EMAIL_ACCOUNT LEFT JOIN DOA_USERS ON DOA_USERS.PK_ACCOUNT_MASTER=DOA_EMAIL_ACCOUNT.PK_ACCOUNT_MASTER WHERE DOA_USERS.PK_USER='$PK_USER'");
        $receiver_name = $details->fields['FIRST_NAME'].' '.$details->fields['LAST_NAME'];

        require_once('global/phpmailer/class.phpmailer.php');
        $mail = new PHPMailer();
        $mail->CharSet =  "utf-8";
        $mail->IsSMTP();
        // enable SMTP authentication
        $mail->SMTPAuth = true;
        // GMAIL username
        $mail->Username = $details->fields['USER_NAME'];
        // GMAIL password
        $mail->Password = $details->fields['PASSWORD'];
        $mail->SMTPSecure = "ssl";
        // sets GMAIL as the SMTP server
        $mail->Host = $details->fields['HOST'];
        // set the SMTP port for the GMAIL server
        $mail->Port = $details->fields['PORT'];
        $mail->From= $details->fields['USER_NAME'];
        $mail->FromName='Doable';
        $mail->AddAddress("$email", "$receiver_name");
        $mail->Subject  =  'Reset Password';
        $mail->IsHTML(true);
        $mail->Body = 'Click On This Link to Reset Password '.$link.'.';
        try {
            if ($mail->Send()) {
                $success_msg = "A password reset link sent to your Mail Id";
            } else {
                pre_r($mail->ErrorInfo);
            }
        } catch (phpmailerException $e) {
        }
    } else {
        $msg = "This Email Id does not exist on our system";
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
                <form class="form-horizontal form-material" action="" method="post">
                    <input type="hidden" name="FUNCTION_NAME" value="resetPasswordFunction">
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
                    <h3 class="text-center m-b-20">Reset Password</h3>
                    <div>
                        <img src="assets/images/background/doable_logo.png" style="margin-left: 33%; height: 60px; width: auto;">
                    </div>

                    <div class="form-group ">
                        <div class="col-xs-12">
                            <input class="form-control" type="text" required="" placeholder="Email" id="EMAIL" name="EMAIL">
                        </div>
                    </div>
                    <div class="form-group text-center">
                        <div class="col-xs-12 p-b-20">
                            <button class="btn w-100 btn-lg btn-info btn-rounded text-white" type="submit">Reset</button>
                        </div>
                    </div>
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