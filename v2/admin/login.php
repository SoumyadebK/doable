<?php
/**
 * Admin login. On the very first login (users table empty), the owner account
 * defined in config.php (ADMIN_EMAIL / ADMIN_PASSWORD) is created automatically.
 */
require_once __DIR__ . '/../includes/functions.php';
$base = base_path();
$error = '';

if (is_logged_in()) { header('Location: ' . $base . '/admin/index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        try {
            // Bootstrap the owner account on first-ever login.
            $count = (int) db()->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
            if ($count === 0 && strcasecmp($email, ADMIN_EMAIL) === 0 && $pass === ADMIN_PASSWORD) {
                $ins = db()->prepare('INSERT INTO users (id, email, password, name, role) VALUES (?,?,?,?,?)');
                $ins->execute([uuidv4(), ADMIN_EMAIL, password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT), 'Owner', 'admin']);
            }

            $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($pass, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name']  = $user['name'] ?: $user['email'];
                header('Location: ' . $base . '/admin/index.php');
                exit;
            }
            $error = 'Invalid email or password.';
        } catch (Throwable $ex) {
            error_log('login failed: ' . $ex->getMessage());
            $error = 'Something went wrong. Please check the database settings in config.php.';
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | <?= e(SITE_NAME) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/styles.css">
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-emerald-50 to-teal-100 px-4">
  <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">
    <div class="text-center mb-6">
      <img src="<?= $base ?>/assets/images/doable-logo.png" alt="<?= e(SITE_NAME) ?>" class="h-10 mx-auto mb-4">
      <h1 class="text-2xl font-bold text-gray-900">Admin Sign In</h1>
      <p class="text-gray-500 text-sm mt-1">Manage your website content, blog, and leads.</p>
    </div>
    <?php if ($error): ?>
      <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="POST" class="space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input name="email" type="email" required autofocus class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input name="password" type="password" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
      </div>
      <button type="submit" class="btn-premium w-full text-center">Sign In</button>
    </form>
    <p class="text-center text-xs text-gray-400 mt-6"><a href="<?= $base ?>/index.php" class="hover:text-emerald-600">&larr; Back to website</a></p>
  </div>
</body>
</html>
