<?php
require_once('global/config.php');
global $db;
global $http_path;

$msg = '';
$success_msg = '';
$FUNCTION_NAME = isset($_POST['FUNCTION_NAME']) ? $_POST['FUNCTION_NAME'] : '';

if ($FUNCTION_NAME == 'resetPasswordFunction') {
    $email = $_POST['EMAIL'];
    $result = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USER_MASTER.PRIMARY_LOCATION_ID, DOA_USER_MASTER.PK_ACCOUNT_MASTER FROM DOA_USERS LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE (DOA_USERS.EMAIL_ID = '$email' OR DOA_USERS.USER_NAME = '$email')");

    if ($result->RecordCount() > 0) {
        $PK_USER = $result->fields['PK_USER'];
        $to = $result->fields['EMAIL_ID'];
        $time = base64_encode($result->fields['PK_USER'] . '_' . time());
        $link = $http_path . 'reset-password.php?cmVzZXQ=' . $time;
        $receiver_name = $result->fields['FIRST_NAME'] . ' ' . $result->fields['LAST_NAME'];

        $selected_roles_row = $db->Execute("SELECT DOA_USER_ROLES.PK_ROLES, DOA_ROLES.SORT_ORDER FROM `DOA_USER_ROLES` LEFT JOIN DOA_ROLES ON DOA_USER_ROLES.PK_ROLES = DOA_ROLES.PK_ROLES WHERE `PK_USER` = '$PK_USER' ORDER BY DOA_ROLES.SORT_ORDER ASC LIMIT 1");
        $selected_role = $selected_roles_row->fields['PK_ROLES'];

        if ($selected_role == 2) {
            $email_account_data = $db->Execute("SELECT * FROM `DOA_SMTP_SETUP` WHERE `PK_SMTP_SETUP` = 1");
        } else {
            $db1 = connectDatabase($result->fields['PK_ACCOUNT_MASTER']);
            $email_account_data = getEmailAccountData($db1, $result->fields['PRIMARY_LOCATION_ID']);
        }

        require_once('global/phpmailer/class.phpmailer.php');
        $mail = new PHPMailer();
        $mail->CharSet = "utf-8";
        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        $mail->Username = $email_account_data->fields['USER_NAME'];
        $mail->Password = $email_account_data->fields['PASSWORD'];
        $mail->SMTPSecure = "ssl";
        $mail->Host = $email_account_data->fields['HOST'];
        $mail->Port = $email_account_data->fields['PORT'];
        $mail->From = $email_account_data->fields['USER_NAME'];
        $mail->FromName = 'Doable';
        $mail->AddAddress("$email", "$receiver_name");
        $mail->Subject = 'Reset Password';
        $mail->IsHTML(true);
        $mail->Body = 'Click On This Link to Reset Password ' . $link . '.';

        try {
            if ($mail->Send()) {
                $success_msg = "A password reset link sent to your Mail Id";
            } else {
                $msg = $mail->Send();
            }
        } catch (phpmailerException $e) {
            $msg = "Error : " . $e->getMessage();
        }
    } else {
        $msg = "This Email Id does not exist on our system";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Doable</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .btn-premium {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            transition: all 0.3s ease;
            border: none;
            font-size: 1rem;
            cursor: pointer;
            display: inline-block;
            width: 100%;
        }

        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4);
            background: linear-gradient(135deg, #059669, #047857);
        }

        .error-box {
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            background-color: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
            font-size: 14px;
        }

        .success-box {
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            background-color: #F0FDF4;
            border: 1px solid #BBF7D0;
            color: #065F46;
            font-size: 14px;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-emerald-50 to-teal-100 px-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-6">
            <img src="assets/images/background/doable_logo.png" alt="Doable" class="h-12 mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-900">Reset Password</h1>
            <p class="text-gray-500 text-sm mt-1">Enter your email to receive a password reset link.</p>
        </div>

        <?php if ($msg): ?>
            <div class="error-box"><?= $msg ?></div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div class="success-box"><?= $success_msg ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="FUNCTION_NAME" value="resetPasswordFunction">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email or Username</label>
                <input name="EMAIL" type="text" required autofocus
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition duration-200"
                    placeholder="Enter your email or username">
            </div>

            <button type="submit" class="btn-premium">
                <i class="fas fa-envelope mr-2"></i> Send Reset Link
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="login.php" class="text-sm text-emerald-600 hover:text-emerald-800 transition duration-200">
                <i class="fas fa-arrow-left mr-1"></i> Back to Login
            </a>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
            <a href="<?= $http_path ?>index.php" class="hover:text-emerald-600 transition duration-200">&larr; Back to website</a>
        </p>
    </div>
</body>

</html>