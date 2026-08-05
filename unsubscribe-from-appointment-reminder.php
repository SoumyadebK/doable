<?
require_once('global/config.php');

$id = $_GET['cmVzZXQ'];
$decodeTime = base64_decode($id);
$new = explode('_', $decodeTime);
$sentTime = $new[1];
$currentTime = time();
$PK_USER = $new[0];

$msg = '';
$success_msg = '';

$result = $db->Execute("SELECT * FROM `DOA_USERS` WHERE PK_USER = '$PK_USER'");
if ($result->RecordCount() > 0) {
    $USER_DATA['IS_UNSUBSCRIBE_FROM_APPOINTMENT_REMINDER_EMAIL'] = 1;
    db_perform('DOA_USERS', $USER_DATA, 'update', "PK_USER =  '$PK_USER'");
    $success_msg = "You have successfully unsubscribed from appointment reminders.";
} else {
    $msg = "Invalid user.";
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

    <title>Unsubscribe from Appointment Reminder</title>

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
                    <?php if ($msg) { ?>
                        <div class="alert alert-danger">
                            <strong><?= $msg; ?></strong>
                        </div>
                    <?php } ?>
                    <?php if ($success_msg) { ?>
                        <div class="alert alert-success">
                            <strong><?= $success_msg; ?></strong>
                        </div>
                    <?php } ?>
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