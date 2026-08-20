<?php
/** Include at the top of every protected admin page. */
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$admin_name = $_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'Admin';
$base = base_path();
