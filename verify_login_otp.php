<?php
require_once('global/config.php');
global $db;
global $http_path;

$msg = '';
$success_msg = '';
$FUNCTION_NAME = isset($_POST['FUNCTION_NAME']) ? $_POST['FUNCTION_NAME'] : '';

if ($FUNCTION_NAME == 'verifyOTPFunction') {
    $TEMP_PK_USER = isset($_SESSION['TEMP_PK_USER']) ? $_SESSION['TEMP_PK_USER'] : '';
    $OTP = isset($_POST['OTP']) ? $_POST['OTP'] : '';

    $user_auth_data = $db->Execute("SELECT * FROM DOA_USER_AUTH_LOG WHERE PK_USER = '$TEMP_PK_USER' AND IS_VERIFIED = 0 ORDER BY LOGIN_TIME DESC LIMIT 1");
    $PK_USER_AUTH_LOG = $user_auth_data->fields['PK_USER_AUTH_LOG'];
    $SAVED_OTP = $user_auth_data->fields['OTP'];
    $LOGIN_ATTEMPTS = $user_auth_data->fields['LOGIN_ATTEMPTS'];

    if ($TEMP_PK_USER && $OTP) {
        unset($_SESSION['OTP_SEND_SUCCESS']);
        if ($SAVED_OTP == $OTP) {
            $db->Execute("UPDATE DOA_USER_AUTH_LOG SET IS_VERIFIED = 1 WHERE PK_USER = '$TEMP_PK_USER' AND OTP = '$OTP' AND IS_VERIFIED = 0");

            $PK_USER = $TEMP_PK_USER;
            $user_data = $db->Execute("SELECT DOA_USERS.*, DOA_ACCOUNT_MASTER.DB_NAME, DOA_ACCOUNT_MASTER.ACTIVE AS ACCOUNT_ACTIVE, DOA_ACCOUNT_MASTER.IS_NEW FROM `DOA_USERS` LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_USERS.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER WHERE DOA_USERS.PK_USER = '$PK_USER'");

            $selected_role = '';
            $selected_roles_row = $db->Execute("SELECT DOA_USER_ROLES.PK_ROLES, DOA_ROLES.SORT_ORDER FROM `DOA_USER_ROLES` LEFT JOIN DOA_ROLES ON DOA_USER_ROLES.PK_ROLES = DOA_ROLES.PK_ROLES WHERE `PK_USER` = '$PK_USER' ORDER BY DOA_ROLES.SORT_ORDER ASC LIMIT 1");
            $selected_role = $selected_roles_row->fields['PK_ROLES'];

            $_SESSION['PK_USER'] = $user_data->fields['PK_USER'];
            $_SESSION['PK_ROLES'] = $selected_role;
            $_SESSION['IS_NEW'] = $user_data->fields['IS_NEW'];

            if ($_SESSION['PK_ROLES'] == 4) {
                $customer_account_data = $db->Execute("SELECT DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER, DOA_ACCOUNT_MASTER.DB_NAME, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_ACCOUNT_MASTER INNER JOIN DOA_USER_MASTER ON DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER  = DOA_USER_MASTER.PK_ACCOUNT_MASTER WHERE DOA_USER_MASTER.PK_USER = '$PK_USER' LIMIT 1");
                $_SESSION['DB_NAME'] = $customer_account_data->fields['DB_NAME'];
                $_SESSION['PK_ACCOUNT_MASTER'] = $customer_account_data->fields['PK_ACCOUNT_MASTER'];
                $_SESSION['PK_USER_MASTER'] = $customer_account_data->fields['PK_USER_MASTER'];
            } elseif ($_SESSION['PK_ROLES'] == 5) {
                $_SESSION['DB_NAME'] = $user_data->fields['DB_NAME'];
                $_SESSION['PK_ACCOUNT_MASTER'] = $user_data->fields['PK_ACCOUNT_MASTER'];
            } elseif ($_SESSION['PK_ROLES'] != 1) {
                $_SESSION['DB_NAME'] = $user_data->fields['DB_NAME'];
                $_SESSION['PK_ACCOUNT_MASTER'] = $user_data->fields['PK_ACCOUNT_MASTER'];
            }

            $_SESSION['FIRST_NAME'] = $user_data->fields['FIRST_NAME'];
            $_SESSION['LAST_NAME'] = $user_data->fields['LAST_NAME'];
            $_SESSION['TICKET_SYSTEM_ACCESS'] = $user_data->fields['TICKET_SYSTEM_ACCESS'];

            if ($_SESSION['PK_ROLES'] == 2 || $_SESSION['PK_ROLES'] == 11) {
                $row = $db->Execute("SELECT PK_LOCATION FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                $LOCATION_ARRAY = [];
                while (!$row->EOF) {
                    $LOCATION_ARRAY[] = $row->fields['PK_LOCATION'];
                    $row->MoveNext();
                }
                $_SESSION['DEFAULT_LOCATION_ID'] = implode(',', $LOCATION_ARRAY);
            } else {
                $selected_location = [];
                $selected_location_row = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` WHERE `PK_USER` = " . $_SESSION['PK_USER']);
                while (!$selected_location_row->EOF) {
                    $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                    $selected_location_row->MoveNext();
                }
                $_SESSION['DEFAULT_LOCATION_ID'] = implode(',', $selected_location);
            }

            if (!file_exists('uploads/' . $_SESSION['PK_ACCOUNT_MASTER'])) {
                mkdir('uploads/' . $_SESSION['PK_ACCOUNT_MASTER'], 0777, true);
                chmod('uploads/' . $_SESSION['PK_ACCOUNT_MASTER'], 0777);
            }

            if ($_SESSION['PK_ROLES'] == 1) {
                header("location: super_admin/all_accounts.php");
            } elseif ($_SESSION['PK_ROLES'] == 4) {
                $account = $db->Execute("SELECT * FROM DOA_USER_MASTER WHERE PK_USER = " . $user_data->fields['PK_USER'] . " LIMIT 1");
                $_SESSION['PK_ACCOUNT_MASTER'] = $account->fields['PK_ACCOUNT_MASTER'];

                if ($account->fields['PRIMARY_LOCATION_ID'] > 0) {
                    $_SESSION['DEFAULT_LOCATION_ID'] = $account->fields['PRIMARY_LOCATION_ID'];
                }

                header("location: customer/all_schedules.php?view=table");
            } elseif ($_SESSION['PK_ROLES'] == 5) {
                header("location: admin_v2/calendar.php");
            } elseif ($_SESSION['IS_NEW'] == 1) {
                header("location: admin/wizard_corporation.php");
            } else {
                header("location: admin_v2/calendar.php");
            }
        } else {
            $UPDATE_DATA['LOGIN_ATTEMPTS'] = $LOGIN_ATTEMPTS + 1;
            db_perform('DOA_USER_AUTH_LOG', $UPDATE_DATA, 'update', ' PK_USER_AUTH_LOG = ' . $PK_USER_AUTH_LOG);

            $msg = "Invalid OTP. Please try again.";
        }
    } else {
        $msg = "OTP is required.";
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

    <title>DOable Login</title>

    <!-- page css -->
    <link href="assets/dist/css/pages/login-register-lock.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/dist/css/style.min.css" rel="stylesheet">

</head>

<body class="skin-default card-no-border">
    <div class="preloader">
        <div class="loader">
            <div class="loader__figure"></div>
            <p class="loader__label">DOable</p>
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
                        <input type="hidden" name="FUNCTION_NAME" value="verifyOTPFunction">
                        <?php if ($msg) { ?>
                            <div class="alert alert-danger">
                                <strong><?= $msg; ?></strong>
                            </div>
                        <?php } ?>
                        <?php if (isset($_SESSION['OTP_SEND_SUCCESS'])) { ?>
                            <div class="alert alert-success" style="font-size: 12px;">
                                <strong><?= $_SESSION['OTP_SEND_SUCCESS']; ?></strong>
                            </div>
                        <?php } ?>
                        <h3 class="text-center m-b-20">Enter OTP</h3>
                        <div>
                            <img src="assets/images/background/doable_logo.png" style="margin-left: 33%; height: 60px; width: auto;">
                        </div>

                        <div class="form-group">
                            <div class="col-xs-12">
                                <input class="form-control" type="text" placeholder="Enter OTP" id="OTP" name="OTP" required>
                            </div>
                        </div>

                        <div class="form-group text-center">
                            <div class="col-xs-12">
                                <button class="btn w-100 btn-lg btn-info btn-rounded text-white" type="submit">Verify OTP</button>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-6">
                                <a href="login.php">Back to Login</a>
                            </div>
                            <div class="col-6">
                                <a href="resend_login_otp.php" style="float: right;">Resend OTP</a>
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