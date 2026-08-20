<?php

/**
 * ============================================================================
 *  DOable — SETTINGS FILE  (edit everything in this file, nothing else)
 * ============================================================================
 *
 *  Your programmer only needs to fill in the values below to connect the site
 *  to your AWS database and email. Do NOT change anything outside the marked
 *  values. After editing, upload the whole folder to your web host.
 *
 *  Quick start for the programmer:
 *    1. Create a MySQL database on AWS (RDS or the server's MySQL).
 *    2. Import  database.sql  into that database (creates all tables + content).
 *    3. Fill in the DATABASE and EMAIL values below.
 *    4. Set ADMIN_EMAIL / ADMIN_PASSWORD — the first time you log in at
 *       /admin/login.php these become your owner account automatically.
 *    5. Point the web server's document root at this folder.
 * ============================================================================
 */

// ----------------------------------------------------------------------------
// 1) DATABASE CONNECTION  (MySQL / MariaDB — e.g. AWS RDS)
// ----------------------------------------------------------------------------
define('DB_HOST', 'localhost');          // e.g. 'your-db.abcdefg.us-east-1.rds.amazonaws.com'
define('DB_NAME', 'DOA_MASTER');             // the database name you created
define('DB_USER', 'root');        // database username
define('DB_PASS', 'b54eawxj5h8ev');          // database password
define('DB_PORT', '3306');               // MySQL port (usually 3306)
define('DB_CHARSET', 'utf8mb4');

// ----------------------------------------------------------------------------
// 2) SITE
// ----------------------------------------------------------------------------
define('SITE_URL',  'https://doable.net');   // full public URL, no trailing slash
define('SITE_NAME', 'DOable');

// Where lead / demo / contact notifications are emailed:
define('LEAD_NOTIFICATION_EMAIL', 'demo@doable.net');

// The "Enroll" button destination. Leave '' (empty) to scroll to the on-site
// contact form. Put a full https:// URL to link to your product/sign-up app.
define('ENROLL_URL', '');

// ----------------------------------------------------------------------------
// 3) EMAIL (SMTP)  — recommended: AWS SES SMTP credentials
// ----------------------------------------------------------------------------
// If you leave SMTP_HOST empty, the site falls back to PHP's built-in mail().
define('SMTP_HOST',   '');               // e.g. 'email-smtp.us-east-1.amazonaws.com'
define('SMTP_PORT',   587);              // 587 (TLS) or 465 (SSL)
define('SMTP_SECURE', 'tls');            // 'tls', 'ssl', or '' for none
define('SMTP_USER',   '');               // SES SMTP username
define('SMTP_PASS',   '');               // SES SMTP password
define('MAIL_FROM',   'noreply@doable.net');   // verified "from" address
define('MAIL_FROM_NAME', 'DOable');

// ----------------------------------------------------------------------------
// 4) OWNER ADMIN ACCOUNT  (used to bootstrap your login the first time only)
// ----------------------------------------------------------------------------
define('ADMIN_EMAIL',    'owner@doable.net');
define('ADMIN_PASSWORD', 'ChangeThisPassword123!');   // change before first login

// ----------------------------------------------------------------------------
//  END OF SETTINGS — do not edit below this line
// ----------------------------------------------------------------------------
ini_set('display_errors', '0');   // set to '1' while debugging on a staging server
error_reporting(E_ALL);
date_default_timezone_set('UTC');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
