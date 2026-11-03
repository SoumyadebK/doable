<?php
/** Handles the homepage contact / free-trial form. */
require_once __DIR__ . '/includes/functions.php';
$base = base_path();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    header('Location: ' . $base . '/index.php');
    exit;
}

$name         = trim($_POST['name'] ?? '');
$email        = trim($_POST['email'] ?? '');
$businessName = trim($_POST['business_name'] ?? '');
$businessType = trim($_POST['business_type'] ?? '');
$phone        = trim($_POST['phone'] ?? '');
$message      = trim($_POST['message'] ?? '');
$smsConsent   = !empty($_POST['sms_consent']);

// Basic validation
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $base . '/index.php?error=1#contact');
    exit;
}

try {
    $stmt = db()->prepare(
        'INSERT INTO contact_submissions
         (id, name, email, business_name, business_type, phone, message, sms_consent, created_at)
         VALUES (:id,:name,:email,:bn,:bt,:phone,:msg,:sms,NOW())'
    );
    $stmt->execute([
        ':id'    => uuidv4(),
        ':name'  => $name,
        ':email' => $email,
        ':bn'    => $businessName ?: null,
        ':bt'    => $businessType ?: null,
        ':phone' => $phone ?: null,
        ':msg'   => $message ?: null,
        ':sms'   => $smsConsent ? 1 : 0,
    ]);
} catch (Throwable $ex) {
    error_log('contact insert failed: ' . $ex->getMessage());
    header('Location: ' . $base . '/index.php?error=1#contact');
    exit;
}

// Notify the business owner (best-effort; failure doesn't block the thank-you).
$body = build_lead_email('New Free-Trial / Contact Request', [
    'Name'          => $name,
    'Email'         => $email,
    'Business name' => $businessName,
    'Business type' => $businessType,
    'Phone'         => $phone,
    'Message'       => $message,
    'SMS consent'   => $smsConsent ? 'Yes' : 'No',
]);
@send_email(LEAD_NOTIFICATION_EMAIL, 'New lead from ' . SITE_NAME, $body, $email);

header('Location: ' . $base . '/index.php?sent=1#contact');
exit;
