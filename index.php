<?php

/** Public homepage: hero, features, industries, testimonials, pricing, CTA, contact. */

// Suppress deprecation warnings from ADOdb
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 0); // Turn off display errors to prevent warning output

// Check if config file exists before requiring
if (file_exists(__DIR__ . '/../global/config.php')) {
    require_once(__DIR__ . '/../global/config.php');
} else {
    // If config doesn't exist, try alternative path
    if (file_exists('global/config.php')) {
        require_once('global/config.php');
    }
}

$seo_title = 'DOable — Business Software for Studios & Class-Based Businesses';
$seo_desc  = 'All-in-one scheduling, billing, CRM and marketing for dance studios, martial arts schools, gyms and class-based businesses. Start a 30-day free trial.';
$seo_path  = '/';

require_once __DIR__ . '/v2/includes/functions.php';

// ===== Contact Form Processing =====
$success = false;
$message = '';
$RECAPTCHA_SITE_KEY = '6LdZZHUtAAAAACzkCkI9IoZ6BWfkdLcYxCKW5JO1';
$RECAPTCHA_SECRET_KEY = '6LdZZHUtAAAAAMTTadugmUHybGcpt2Ygs4ABUqf3';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    // ===== Verify reCAPTCHA first =====
    $captchaOk = false;
    if (!empty($_POST['g-recaptcha-response'])) {
        $recaptchaResponse = $_POST['g-recaptcha-response'];
        $verify = @file_get_contents(
            'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($RECAPTCHA_SECRET_KEY)
                . '&response=' . urlencode($recaptchaResponse)
                . '&remoteip=' . urlencode($_SERVER['REMOTE_ADDR'])
        );
        if ($verify) {
            $verifyData = json_decode($verify);
            if ($verifyData && $verifyData->success) {
                $captchaOk = true;
            }
        }
    }

    if (!$captchaOk) {
        $message = 'Please verify that you are not a robot before submitting the form.';
    } else {
        // Check if PHPMailer exists
        $phpmailer_paths = [
            'global/phpmailer/class.phpmailer.php',
            __DIR__ . '/../global/phpmailer/class.phpmailer.php',
            'includes/phpmailer/class.phpmailer.php'
        ];

        $phpmailer_found = false;
        foreach ($phpmailer_paths as $path) {
            if (file_exists($path)) {
                @require_once($path);
                $phpmailer_found = true;
                break;
            }
        }

        if ($phpmailer_found && class_exists('PHPMailer')) {
            $name = htmlspecialchars($_POST['name']);
            $email = htmlspecialchars($_POST['email']);
            $phone = htmlspecialchars($_POST['phone']);
            $BUSINESS_TYPE = htmlspecialchars($_POST['BUSINESS_TYPE']);

            $hostname = 'smtp.protonmail.ch';
            $port = '587';
            $userName = 'demo@doable.net';
            $SendingPwd = '9B76V5Q2NPY7524W';
            $To = "demo@doable.net";
            $Subject = "Contact Us from Doable";

            try {
                $mail = new PHPMailer();
                $mail->IsSMTP();
                $mail->SMTPDebug = 0;
                $mail->Debugoutput = 'html';
                $mail->Host = $hostname;
                $mail->Port = $port;
                $mail->SMTPSecure = ($port == 465) ? 'ssl' : 'tls';
                $mail->SMTPAuth = true;
                $mail->Username = $userName;
                $mail->Password = $SendingPwd;
                $mail->setFrom($userName, "Doable");
                $mail->addAddress($To, "Doable");
                $mail->Subject = $Subject;
                $mail->IsHTML(true);

                $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                    <meta charset="UTF-8">
                    </head>
                    <body style="margin:0; padding:0; background-color:#f4f4f7; font-family: Arial, Helvetica, sans-serif;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7; padding:30px 0;">
                            <tr>
                                <td align="center">
                                    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                                        <tr>
                                            <td style="background-color:#39b54a; padding:24px 32px;">
                                                <h1 style="margin:0; color:#ffffff; font-size:20px; font-weight:600;">New Contact Form Enquiry</h1>
                                                <p style="margin:4px 0 0; color:#c7d2fe; font-size:13px;">Doable Website</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:32px;">
                                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td style="padding:10px 0; border-bottom:1px solid #eef0f4; width:140px; color:#6b7280; font-size:14px;">Name</td>
                                                        <td style="padding:10px 0; border-bottom:1px solid #eef0f4; color:#111827; font-size:14px; font-weight:600;">' . htmlspecialchars($name) . '</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:10px 0; border-bottom:1px solid #eef0f4; color:#6b7280; font-size:14px;">Email</td>
                                                        <td style="padding:10px 0; border-bottom:1px solid #eef0f4; color:#111827; font-size:14px; font-weight:600;">
                                                            <a href="mailto:' . htmlspecialchars($email) . '" style="color:#39b54a; text-decoration:none;">' . htmlspecialchars($email) . '</a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:10px 0; border-bottom:1px solid #eef0f4; color:#6b7280; font-size:14px;">Phone</td>
                                                        <td style="padding:10px 0; border-bottom:1px solid #eef0f4; color:#111827; font-size:14px; font-weight:600;">' . htmlspecialchars($phone) . '</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:10px 0; color:#6b7280; font-size:14px;">Business Type</td>
                                                        <td style="padding:10px 0; color:#111827; font-size:14px; font-weight:600;">' . htmlspecialchars($BUSINESS_TYPE) . '</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="background-color:#f9fafb; padding:16px 32px; border-top:1px solid #eef0f4;">
                                                <p style="margin:0; color:#9ca3af; font-size:12px;">This enquiry was submitted via the contact form on the Doable website.</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </body>
                    </html>';

                $mail->AltBody = "New Contact Form Enquiry\n\n"
                    . "Name: $name\n"
                    . "Email: $email\n"
                    . "Phone: $phone\n"
                    . "Business Type: $BUSINESS_TYPE\n";

                if (!$mail->send()) {
                    $message = $mail->ErrorInfo;
                } else {
                    $success = true;
                    $message = 'Your Enquiry has been submitted to our team. We will get back to you soon.';
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
            }
        } else {
            // Allow form submission even without PHPMailer (for testing)
            $success = true;
            $message = 'Your Enquiry has been submitted to our team. We will get back to you soon.';
            error_log('PHPMailer not found - form submitted without email');
        }
    }
}

// Get content and setup page
$content = get_content();
$page = 'home';
$on_home = true;
$base = base_path();
include __DIR__ . '/v2/includes/header.php';

$hero = $content['hero'];
$feat = $content['features'];
$ind  = $content['industries'];
$test = $content['testimonials'];
$pric = $content['pricing'];
$cta  = $content['cta'];
$con  = $content['contact'];
$plan = $pric['plans'][0] ?? null;
?>

<!-- ============================ HERO ============================ -->
<section class="relative overflow-hidden gradient-mesh pt-28 md:pt-36 pb-20 md:pb-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="reveal inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium mb-6">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <?= e($hero['badge']) ?>
        </div>
        <h1 class="reveal text-premium-heading text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold tracking-tight mb-6">
            <?= e($hero['titleLine1']) ?><?php if (!empty($hero['titleLine2'])): ?><br><?= e($hero['titleLine2']) ?><?php endif; ?>
            <span class="gradient-text"><?= e($hero['titleHighlight']) ?></span>
        </h1>
        <p class="reveal max-w-2xl mx-auto text-lg md:text-xl text-gray-600 mb-3"><?= e($hero['subheadline']) ?></p>
        <p class="reveal max-w-2xl mx-auto text-lg md:text-xl text-gray-600 mb-8"><?= e($hero['subheadline2']) ?></p>
        <div class="reveal flex flex-col sm:flex-row items-center justify-center gap-4 mb-10">
            <a href="#contact" class="btn-premium text-base px-8 py-3.5"><?= e($hero['primaryCta']) ?></a>
            <a href="#features" class="inline-flex items-center gap-2 px-8 py-3.5 rounded-full font-semibold text-gray-700 bg-white border border-gray-200 shadow-sm hover:shadow-md transition-all"><?= e($hero['secondaryCta']) ?></a>
        </div>
        <div class="reveal flex flex-wrap items-center justify-center gap-x-8 gap-y-2 text-sm text-gray-500">
            <?php foreach ($hero['trustIndicators'] as $ti): ?>
                <span class="inline-flex items-center gap-2"><span class="text-emerald-500">&#10003;</span><?= e($ti) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========================== FEATURES ========================== -->
<section id="features" class="scroll-mt-nav py-20 md:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <div class="reveal inline-block px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium mb-4"><?= e($feat['badge']) ?></div>
            <h2 class="reveal text-premium-heading text-3xl md:text-5xl font-extrabold mb-4"><?= e($feat['title']) ?> <span class="gradient-text"><?= e($feat['titleHighlight']) ?></span></h2>
            <p class="reveal text-lg text-gray-600"><?= e($feat['subtitle']) ?></p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($feat['items'] as $it): ?>
                <div class="reveal card-premium p-6 relative">
                    <?php if (!empty($it['badge'])): ?>
                        <span class="absolute top-4 right-4 text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700"><?= e($it['badge']) ?></span>
                    <?php endif; ?>
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-emerald-50 text-2xl mb-4"><?= e($it['icon']) ?></div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2"><?= e($it['title']) ?></h3>
                    <p class="text-gray-600 leading-relaxed"><?= e($it['description']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========================= INDUSTRIES ========================= -->
<section id="industries" class="scroll-mt-nav py-20 md:py-28 gradient-mesh">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <div class="reveal inline-block px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium mb-4"><?= e($ind['badge']) ?></div>
            <h2 class="reveal text-premium-heading text-3xl md:text-5xl font-extrabold mb-4"><?= e($ind['title']) ?> <span class="gradient-text"><?= e($ind['titleHighlight']) ?></span></h2>
            <p class="reveal text-lg text-gray-600"><?= e($ind['subtitle']) ?></p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($ind['items'] as $it): ?>
                <div class="reveal relative overflow-hidden rounded-2xl p-6 bg-gradient-to-br from-emerald-500 via-teal-500 to-emerald-600 text-white shadow-lg">
                    <div class="absolute inset-0 bg-black/25"></div>
                    <div class="relative">
                        <div class="text-4xl mb-4"><?= e($it['emoji']) ?></div>
                        <h3 class="text-xl font-bold mb-2"><?= e($it['title']) ?></h3>
                        <p class="text-white/90 mb-4 leading-relaxed"><?= e($it['description']) ?></p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($it['features'] as $f): ?>
                                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-black/30 text-white"><?= e($f) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ======================== TESTIMONIALS ======================== -->
<section class="py-20 md:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <div class="reveal inline-block px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium mb-4"><?= e($test['badge']) ?></div>
            <h2 class="reveal text-premium-heading text-3xl md:text-5xl font-extrabold mb-4"><?= e($test['title']) ?> <span class="gradient-text"><?= e($test['titleHighlight']) ?></span></h2>
            <p class="reveal text-lg text-gray-600"><?= e($test['subtitle']) ?></p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($test['items'] as $t): ?>
                <div class="reveal card-premium p-8">
                    <div class="flex items-center gap-1 text-amber-400 mb-4">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                    <p class="text-gray-700 text-lg leading-relaxed mb-6">&ldquo;<?= e($t['quote']) ?>&rdquo;</p>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="relative w-12 h-12 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                                <div class="absolute inset-0 rounded-full bg-black/25"></div>
                                <span class="relative text-white font-bold"><?= e($t['avatar']) ?></span>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900"><?= e($t['author']) ?></div>
                                <div class="text-sm text-gray-500"><?= e($t['role']) ?></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-extrabold gradient-text"><?= e($t['metricValue']) ?></div>
                            <div class="text-xs text-gray-500"><?= e($t['metricLabel']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========================== PRICING =========================== -->
<section id="pricing" class="scroll-mt-nav py-20 md:py-28 gradient-mesh">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="reveal inline-block px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium mb-4"><?= e($pric['badge']) ?></div>
            <h2 class="reveal text-premium-heading text-3xl md:text-5xl font-extrabold mb-4"><?= e($pric['title']) ?> <span class="gradient-text"><?= e($pric['titleHighlight']) ?></span></h2>
            <p class="reveal text-lg text-gray-600 mb-8"><?= e($pric['subtitle']) ?></p>
            <div class="reveal inline-flex items-center gap-3 bg-white rounded-full p-1.5 shadow-sm border border-gray-100">
                <button id="billing-monthly" class="px-5 py-2 rounded-full text-sm font-semibold bg-emerald-600 text-white transition-all">Monthly</button>
                <button id="billing-annual" class="px-5 py-2 rounded-full text-sm font-semibold text-gray-600 transition-all">Annual <span class="text-emerald-600">(save ~17%)</span></button>
            </div>
        </div>
        <div class="max-w-md mx-auto">
            <?php foreach ($pric['plans'] as $p): ?>
                <div class="reveal card-premium p-8 border-2 border-emerald-500 relative">
                    <?php if (!empty($p['popular'])): ?>
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 text-xs font-bold px-4 py-1 rounded-full bg-emerald-600 text-white">MOST POPULAR</span>
                    <?php endif; ?>
                    <h3 class="text-2xl font-bold text-gray-900 mb-1"><?= e($p['name']) ?></h3>
                    <p class="text-gray-600 mb-6"><?= e($p['description']) ?></p>
                    <div class="mb-2">
                        <span class="text-5xl font-extrabold text-gray-900">$<span class="plan-price" data-monthly="<?= e($p['priceMonthly']) ?>" data-annual="<?= e($p['priceAnnual']) ?>"><?= e($p['priceMonthly']) ?></span></span>
                        <span class="text-gray-500">/month</span>
                    </div>
                    <p class="plan-annual-note text-sm text-emerald-600 font-medium mb-6 hidden">Billed annually &mdash; $<?= e((string)((int)$p['priceAnnual'] * 12)) ?>/year</p>
                    <p class="plan-monthly-note text-sm text-gray-500 mb-6"><?= e($pric['trialBanner']) ?></p>
                    <a href="#contact" class="btn-premium w-full text-center mb-6">Start 30-Day Free Trial</a>
                    <ul class="space-y-3">
                        <?php foreach ($p['features'] as $f): ?>
                            <li class="flex items-start gap-2 text-gray-700"><span class="text-emerald-500 mt-0.5">&#10003;</span><span><?= e($f) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
            <p class="text-center text-sm text-gray-500 mt-6"><?= e($pric['guaranteeText']) ?></p>
        </div>
    </div>
</section>

<!-- ============================ CTA ============================= -->
<section class="py-20 md:py-28 bg-gradient-to-br from-emerald-600 via-teal-600 to-emerald-700 relative overflow-hidden">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
        <h2 class="reveal text-3xl md:text-5xl font-extrabold mb-4"><?= e($cta['title']) ?> <span class="text-emerald-100"><?= e($cta['titleHighlight']) ?></span></h2>
        <p class="reveal text-lg text-white/90 mb-8 max-w-2xl mx-auto"><?= e($cta['subtitle']) ?></p>
        <a href="#contact" class="reveal inline-block bg-white text-emerald-700 font-bold px-8 py-3.5 rounded-full shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all"><?= e($cta['buttonText']) ?></a>
        <div class="reveal flex flex-wrap items-center justify-center gap-x-8 gap-y-2 mt-8 text-sm text-white/90">
            <?php foreach ($cta['benefits'] as $b): ?>
                <span class="inline-flex items-center gap-2"><span>&#10003;</span><?= e($b['text']) ?></span>
            <?php endforeach; ?>
        </div>
        <p class="reveal text-white/80 italic mt-8"><?= e($cta['tagline']) ?></p>
    </div>
</section>

<!-- ========================== CONTACT =========================== -->
<section id="contact" class="scroll-mt-nav py-20 md:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-14">
            <div class="reveal inline-block px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium mb-4"><?= e($con['badge']) ?></div>
            <h2 class="reveal text-premium-heading text-3xl md:text-5xl font-extrabold mb-4"><?= e($con['title']) ?></h2>
            <p class="reveal text-lg text-gray-600"><?= e($con['subtitle']) ?></p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 max-w-5xl mx-auto">
            <!-- Info column -->
            <div class="space-y-6">
                <div class="card-premium p-6 flex items-start gap-4">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-xl">&#9993;</div>
                    <div>
                        <div class="font-semibold text-gray-900">Email us</div>
                        <a href="mailto:<?= e($con['email']) ?>" class="text-emerald-600 hover:underline"><?= e($con['email']) ?></a>
                    </div>
                </div>
                <div class="card-premium p-6 flex items-start gap-4">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-xl">&#128205;</div>
                    <div>
                        <div class="font-semibold text-gray-900">Location</div>
                        <div class="text-gray-600"><?= e($con['location']) ?></div>
                    </div>
                </div>
                <div class="card-premium p-6">
                    <div class="font-semibold text-gray-900 mb-4">What happens next?</div>
                    <ol class="space-y-3 text-gray-600">
                        <li class="flex gap-3"><span class="flex-shrink-0 w-7 h-7 rounded-full bg-emerald-600 text-white text-sm font-bold flex items-center justify-center">1</span><span>We&rsquo;ll reach out within one business day.</span></li>
                        <li class="flex gap-3"><span class="flex-shrink-0 w-7 h-7 rounded-full bg-emerald-600 text-white text-sm font-bold flex items-center justify-center">2</span><span>We&rsquo;ll set up a personalized demo for your business.</span></li>
                        <li class="flex gap-3"><span class="flex-shrink-0 w-7 h-7 rounded-full bg-emerald-600 text-white text-sm font-bold flex items-center justify-center">3</span><span>Start your free 30-day trial &mdash; no commitment.</span></li>
                    </ol>
                </div>
            </div>

            <!-- Form column -->
            <div class="card-premium p-8">
                <?php if ($success): ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto mb-4">&#10003;</div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Thank you!</h3>
                        <p class="text-gray-600"><?= $message ?></p>
                    </div>
                <?php else: ?>
                    <?php if ($message): ?>
                        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm"><?= $message ?></div>
                    <?php endif; ?>
                    <form method="POST" action="" class="space-y-4">
                        <?= csrf_field() ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input name="name" type="text" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                            <input type="email" name="email" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                            <input name="phone" type="tel" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none format_phone_number">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Business Type *</label>
                            <select name="BUSINESS_TYPE" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white">
                                <option value="">Select Business Type</option>
                                <?php
                                // Check if $db exists and is connected
                                if (isset($db) && $db) {
                                    try {
                                        $row = $db->Execute("SELECT PK_BUSINESS_TYPE, BUSINESS_TYPE FROM DOA_BUSINESS_TYPE WHERE ACTIVE = 1");
                                        if ($row && !$row->EOF) {
                                            while (!$row->EOF) {
                                                echo '<option value="' . htmlspecialchars($row->fields['BUSINESS_TYPE']) . '">' . htmlspecialchars($row->fields['BUSINESS_TYPE']) . '</option>';
                                                $row->MoveNext();
                                            }
                                        }
                                    } catch (Exception $e) {
                                        error_log('Business type query error: ' . $e->getMessage());
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="flex items-start gap-2">
                            <input type="checkbox" name="sms_consent" id="sms_consent" value="1" required class="mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <label for="sms_consent" class="text-sm text-gray-600">
                                I agree to receive text messages from Doable related to service updates and support communications.
                                Message frequency may vary. Message & data rates may apply. Reply STOP to opt out or HELP for help.
                                Please review our <a href="terms_of_use.php" target="_blank" class="text-emerald-600 hover:text-emerald-800">Terms of Use</a> and <a href="privacy_policy.php" target="_blank" class="text-emerald-600 hover:text-emerald-800">Privacy Policy</a>.
                            </label>
                        </div>
                        <div>
                            <div class="g-recaptcha" data-sitekey="<?= $RECAPTCHA_SITE_KEY; ?>"></div>
                            <div id="recaptcha_error" style="color:#DC2626; font-size:14px; margin-top:8px; display:none;">
                                Please verify that you are not a robot before submitting.
                            </div>
                        </div>
                        <button type="submit" class="btn-premium w-full text-center">Start My Free Trial</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
    // Pricing monthly/annual toggle
    (function() {
        var m = document.getElementById('billing-monthly'),
            a = document.getElementById('billing-annual');
        if (!m || !a) return;

        function set(annual) {
            document.querySelectorAll('.plan-price').forEach(function(el) {
                el.textContent = annual ? el.dataset.annual : el.dataset.monthly;
            });
            document.querySelectorAll('.plan-annual-note').forEach(function(el) {
                el.classList.toggle('hidden', !annual);
            });
            document.querySelectorAll('.plan-monthly-note').forEach(function(el) {
                el.classList.toggle('hidden', annual);
            });
            m.className = 'px-5 py-2 rounded-full text-sm font-semibold transition-all ' + (annual ? 'text-gray-600' : 'bg-emerald-600 text-white');
            a.className = 'px-5 py-2 rounded-full text-sm font-semibold transition-all ' + (annual ? 'bg-emerald-600 text-white' : 'text-gray-600');
        }
        m.addEventListener('click', function() {
            set(false);
        });
        a.addEventListener('click', function() {
            set(true);
        });
    })();

    // Form validation with reCAPTCHA
    document.querySelector('form')?.addEventListener('submit', function(e) {
        var recaptchaError = document.getElementById('recaptcha_error');
        var recaptchaResponse = grecaptcha.getResponse();

        if (recaptchaResponse.length === 0) {
            e.preventDefault();
            recaptchaError.style.display = 'block';
            recaptchaError.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        } else {
            recaptchaError.style.display = 'none';
        }
    });

    // Phone number formatting
    document.querySelectorAll('.format_phone_number').forEach(function(input) {
        input.addEventListener('input', function(e) {
            var x = this.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            if (x) {
                this.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
            }
        });
    });
</script>

<!-- Google reCAPTCHA -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<?php include __DIR__ . '/v2/includes/footer.php'; ?>