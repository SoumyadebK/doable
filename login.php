<?
require_once('global/config.php');
$msg = '';
$success_msg = '';
$FUNCTION_NAME = isset($_POST['FUNCTION_NAME']) ? $_POST['FUNCTION_NAME'] : '';

if ($FUNCTION_NAME == 'loginFunction'){
    $USER_NAME = trim($_POST['USER_NAME']);
    $PASSWORD = trim($_POST['PASSWORD']);

    $result = $db->Execute("SELECT DOA_USERS.*, DOA_ACCOUNT_MASTER.DB_NAME, DOA_ACCOUNT_MASTER.ACTIVE AS ACCOUNT_ACTIVE FROM `DOA_USERS` LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_USERS.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER WHERE DOA_USERS.USER_NAME = '$USER_NAME'");
    if($result->RecordCount() > 0) {
        if (($result->fields['ACCOUNT_ACTIVE'] == 1 || $result->fields['ACCOUNT_ACTIVE'] == '' || $result->fields['ACCOUNT_ACTIVE'] == NULL) && $result->fields['ACTIVE'] == 1 && $result->fields['CREATE_LOGIN'] == 1) {
            if (password_verify($PASSWORD, $result->fields['PASSWORD'])) {
                $selected_roles = [];
                $PK_USER = $result->fields['PK_USER'];
                $selected_roles_row = $db->Execute("SELECT PK_ROLES FROM `DOA_USER_ROLES` WHERE `PK_USER` = '$PK_USER'");
                while (!$selected_roles_row->EOF) {
                    $selected_roles[] = $selected_roles_row->fields['PK_ROLES'];
                    $selected_roles_row->MoveNext();
                }

                $_SESSION['PK_USER'] = $result->fields['PK_USER'];

                if (in_array(1, $selected_roles)) {
                    $_SESSION['PK_ROLES'] = 1;
                } elseif (in_array(2, $selected_roles)) {
                    $_SESSION['PK_ROLES'] = 2;
                    $_SESSION['DB_NAME'] = $result->fields['DB_NAME'];
                    $_SESSION['PK_ACCOUNT_MASTER'] = $result->fields['PK_ACCOUNT_MASTER'];
                } elseif (in_array(4, $selected_roles)) {
                    $customer_account_data = $db->Execute("SELECT DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER, DOA_ACCOUNT_MASTER.DB_NAME FROM DOA_ACCOUNT_MASTER INNER JOIN DOA_USER_MASTER ON DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER  = DOA_USER_MASTER.PK_ACCOUNT_MASTER WHERE DOA_USER_MASTER.PK_USER = '$PK_USER' LIMIT 1");
                    $_SESSION['PK_ROLES'] = 4;
                    $_SESSION['DB_NAME'] = $customer_account_data->fields['DB_NAME'];
                    $_SESSION['PK_ACCOUNT_MASTER'] = $customer_account_data->fields['PK_ACCOUNT_MASTER'];
                } elseif (in_array(5, $selected_roles)) {
                    $_SESSION['PK_ROLES'] = 5;
                    $_SESSION['DB_NAME'] = $result->fields['DB_NAME'];
                    $_SESSION['PK_ACCOUNT_MASTER'] = $result->fields['PK_ACCOUNT_MASTER'];
                }

                $_SESSION['FIRST_NAME'] = $result->fields['FIRST_NAME'];
                $_SESSION['LAST_NAME'] = $result->fields['LAST_NAME'];
                $_SESSION['ACCESS_TOKEN'] = $result->fields['ACCESS_TOKEN'];
                $_SESSION['TICKET_SYSTEM_ACCESS'] = $result->fields['TICKET_SYSTEM_ACCESS'];

                $row = $db->Execute("SELECT PK_LOCATION FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                $LOCATION_ARRAY = [];
                while (!$row->EOF) {
                    $LOCATION_ARRAY[] = $row->fields['PK_LOCATION'];
                    $row->MoveNext();
                }
                $_SESSION['DEFAULT_LOCATION_ID'] = implode(',', $LOCATION_ARRAY);

                if ($_SESSION['PK_ROLES'] == 1) {
                    header("location: super_admin/all_accounts.php");
                } elseif ($_SESSION['PK_ROLES'] == 2) {
                    header("location: admin/all_schedules.php?view=table");
                } elseif ($_SESSION['PK_ROLES'] == 4) {
                    $account = $db->Execute("SELECT * FROM DOA_USER_MASTER WHERE PK_USER = ".$result->fields['PK_USER']." LIMIT 1");
                    $_SESSION['PK_ACCOUNT_MASTER'] = $account->fields['PK_ACCOUNT_MASTER'];
                    header("location: customer/all_schedules.php?view=table");
                } elseif ($_SESSION['PK_ROLES'] == 5) {
                    header("location: service_provider/all_schedules.php?view=table");
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
                    <input type="hidden" name="FUNCTION_NAME" value="loginFunction">
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
                            <input class="form-control" type="text" required="" placeholder="Username" id="USER_NAME" name="USER_NAME">
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
                                    <a href=" " id="to-recover" class="text-muted"><i class="fas fa-lock m-r-5"></i> Forgot password?</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group text-center">
                        <div class="col-xs-12 p-b-20">
                            <button class="btn w-100 btn-lg btn-info btn-rounded text-white" type="submit">Log In</button>
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