<?php

/** Shared helpers: escaping, UUID, CSRF, auth, slug, mailer, content. */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/content.php';

/** HTML-escape */
function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/** RFC4122-ish v4 UUID (works for our CHAR(36) primary keys) */
function uuidv4(): string
{
    $d = random_bytes(16);
    $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
    $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
}

/** CSRF token helpers */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/** URL-friendly slug */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'post';
}

/** Auth: is an admin logged in? */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}
function require_admin(): void
{
    if (is_logged_in()) {
        header('Location: ' . base_path() . '/admin/login.php');
        exit;
    }
}

/** Base path of the app relative to the web root (so links work in subfolders). */
function base_path(): string
{
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // If we're inside /admin, step up one level to the site root.
    if (substr($script, -6) === '/admin') {
        $script = substr($script, 0, -6);
    }
    return rtrim($script, '/');
}

/** The Enroll button URL + whether it is external. */
function enroll_link(): array
{
    $url = defined('ENROLL_URL') ? trim(ENROLL_URL) : '';
    $external = (bool)preg_match('#^https?://#i', $url);
    if (!$external) {
        $url = base_path() . '/#contact';
    }
    return [$url, $external];
}

/* -------------------------------------------------------------------------- */
/*  Email                                                                     */
/* -------------------------------------------------------------------------- */

/** Send an email via SMTP (if configured) or PHP mail(). Returns true on success. */
function send_email(string $to, string $subject, string $htmlBody, string $replyTo = ''): bool
{
    if (defined('SMTP_HOST') && SMTP_HOST !== '') {
        return smtp_send($to, $subject, $htmlBody, $replyTo);
    }
    // Fallback: PHP mail()
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
    $headers .= 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>' . "\r\n";
    if ($replyTo !== '') {
        $headers .= 'Reply-To: ' . $replyTo . "\r\n";
    }
    return @mail($to, $subject, $htmlBody, $headers);
}

/** Minimal SMTP client (supports STARTTLS / SSL + AUTH LOGIN). */
function smtp_send(string $to, string $subject, string $htmlBody, string $replyTo = ''): bool
{
    $host = SMTP_HOST;
    $port = (int)SMTP_PORT;
    $secure = strtolower(SMTP_SECURE);
    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;

    $fp = @stream_socket_client($remote, $errno, $errstr, 20);
    if (!$fp) {
        return false;
    }
    stream_set_timeout($fp, 20);

    $read = function () use ($fp) {
        $data = '';
        while ($line = fgets($fp, 515)) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    };
    $cmd = function ($c) use ($fp, $read) {
        fwrite($fp, $c . "\r\n");
        return $read();
    };

    $read();
    $ehlo = 'doable.' . preg_replace('/[^a-z0-9.\-]/i', '', parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost');
    $cmd('EHLO ' . $ehlo);

    if ($secure === 'tls') {
        $cmd('STARTTLS');
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($fp);
            return false;
        }
        $cmd('EHLO ' . $ehlo);
    }

    if (SMTP_USER !== '') {
        $cmd('AUTH LOGIN');
        $cmd(base64_encode(SMTP_USER));
        $auth = $cmd(base64_encode(SMTP_PASS));
        if (strpos($auth, '235') === false) {
            fclose($fp);
            return false;
        }
    }

    $cmd('MAIL FROM:<' . MAIL_FROM . '>');
    $rcpt = $cmd('RCPT TO:<' . $to . '>');
    if (strpos($rcpt, '25') === false) {
        fclose($fp);
        return false;
    }
    $cmd('DATA');

    $headers  = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>' . "\r\n";
    $headers .= 'To: <' . $to . '>' . "\r\n";
    if ($replyTo !== '') {
        $headers .= 'Reply-To: ' . $replyTo . "\r\n";
    }
    $headers .= 'Subject: ' . $subject . "\r\n";
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
    $body = str_replace("\n.", "\n..", $htmlBody);
    $resp = $cmd($headers . "\r\n" . $body . "\r\n.");
    $cmd('QUIT');
    fclose($fp);
    return strpos($resp, '250') !== false;
}

/** Build a simple lead-notification email body. */
function build_lead_email(string $heading, array $fields): string
{
    $rows = '';
    foreach ($fields as $label => $val) {
        if ($val === '' || $val === null) continue;
        $rows .= '<tr><td style="padding:6px 12px;font-weight:600;color:#065f46;">'
            . e($label) . '</td><td style="padding:6px 12px;color:#111;">'
            . nl2br(e($val)) . '</td></tr>';
    }
    return '<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;">'
        . '<h2 style="color:#059669;">' . e($heading) . '</h2>'
        . '<table style="border-collapse:collapse;width:100%;background:#f9fafb;border-radius:8px;">'
        . $rows . '</table>'
        . '<p style="color:#6b7280;font-size:12px;margin-top:16px;">Sent from ' . e(SITE_NAME) . ' (' . e(SITE_URL) . ')</p>'
        . '</div>';
}
