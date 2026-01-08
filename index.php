<?php
global $db;
require_once('global/config.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>DOable – Stop Juggling. Start Growing.</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.5.0/css/fontawesome.min.css" rel="stylesheet">

    <style>
        :root {
            --green: #39b54a;
            --green-dark: #39b54a;
            --green-soft: #dcfce7;
            --ink: #0f172a;
            --muted: #64748b;
            --bg: #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--ink);
        }

        .btn-primary {
            background: var(--green);
            border-color: var(--green);
            font-weight: 600;
            padding: .75rem 1.25rem;
            border-radius: .75rem;
        }

        .btn-primary:hover {
            background: var(--green-dark);
            border-color: var(--green-dark);
        }

        .badge-soft {
            background: var(--green-soft);
            color: var(--green-dark);
            padding: .5rem 1rem;
            border-radius: 50rem;
            font-weight: 500;
        }

        h1 {
            font-weight: 800;
            letter-spacing: -1px;
        }

        h2 {
            font-weight: 800;
            letter-spacing: -.6px;
        }

        .text-green {
            color: var(--green)
        }

        .text-muted-custom {
            color: var(--muted)
        }

        .card {
            border-radius: 1.25rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
            border: 0;
        }

        .icon-box {
            width: 48px;
            height: 48px;
            /* background:var(--green-soft); */
            background: #39b54a;
            /* color:var(--green-dark); */
            color: #fff;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .btn-gradient {
            background: #39b54a;
        }

        .icon-box svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        section {
            padding: 5rem 0
        }

        .bg-soft {
            background: var(--bg)
        }

        .nav-new {
            margin: 0 auto;
            width: 100%;
            box-shadow: 1px 7px 8px 0px rgba(0, 0, 0, 0.47);
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 9;
        }

        .quotes-img {
            position: absolute;
            top: -15px;
            left: -10px;
        }

        .trusted-section {
            border-top: 1px solid #e5e7eb;
            padding-top: 32px;
            padding-bottom: 32px;
            width: 50%;
            margin: 0 auto;
        }

        .trusted-title {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .trusted-list {
            max-width: 720px;
            margin: 0 auto;
        }

        .trusted-item {
            font-size: 18px;
            color: #6b7280;
            padding: 0 20px;
            white-space: nowrap;
        }

        .trusted-divider {
            width: 1px;
            height: 16px;
            background-color: #e5e7eb;
        }

        .bg-green-light {
            background-color: #edfdf4;
        }

        .color-star {
            color: #facc15;
        }

        .what-nos li::marker {
            color: #39b54a;
            font-weight: 600;
        }

        .what-nos li {
            color: #333;
        }

        .contact-block {
            display: flex;
            flex-direction: column;
            gap: 24px;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .icon-badge {
            width: 44px;
            height: 44px;
            background-color: #dcfce7;
            /* light green */
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .contact-title {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            /* dark heading */
            margin-bottom: 4px;
        }

        .contact-subtitle {
            font-size: 14px;
            color: #475569;
            /* muted gray */
            line-height: 1.5;
        }

        .form-card {
            max-width: 420px;
            background: #ffffff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 6px;
            display: block;
        }

        label span {
            color: #39b54a;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            opacity: 0.6;
        }

        .textarea .input-icon {
            top: 20%;
        }

        .input-wrapper input,
        .input-wrapper select,
        .input-wrapper textarea {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 14px;
            outline: none;
        }

        .input-wrapper textarea {
            resize: none;
        }

        .input-wrapper input:focus,
        .input-wrapper select:focus,
        .input-wrapper textarea:focus {
            border-color: #39b54a;
        }

        .consent-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background-color: #edfdf4;
            padding: 14px;
            border-radius: 8px;
            font-size: 12px;
            color: #475569;
            margin-bottom: 18px;
        }

        .consent-box input {
            margin-top: 3px;
        }

        .consent-box a {
            color: #39b54a;
            text-decoration: none;
            font-weight: 500;
        }

        .submit-btn {
            width: 100%;
            background: #39b54a;
            border: none;
            color: #ffffff;
            font-size: 15px;
            font-weight: 600;
            padding: 14px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            gap: 8px;
            align-items: center;
        }

        .submit-btn:hover {
            opacity: 0.95;
        }

        .form-footer {
            font-size: 11px;
            color: #64748b;
            text-align: center;
            margin-top: 14px;
            line-height: 1.4;
        }

        .bg-green {
            background-color: #39b54a;
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 20px;
            margin-right: 5px;
            border: 2px solid #39b54a;
            padding: 2px;
        }
    </style>
</head>

<body>

    <!-- NAV -->
    <nav class="container-fluid d-flex justify-content-between align-items-center py-3 px-5 nav-new">
        <div class="fw-bold fs-4">
            <a href="index.php">
                <img width="150" src="demo1/images/doable_logo.png" />
            </a>
        </div>

        <div class="ms-auto d-flex gap-2">
            <a href="contact_us.php" class="btn btn-primary">Request Demo</a>
            <a href="login.php" class="btn btn-primary">Login</a>
        </div>
    </nav>

    <!-- HERO -->
    <section class="text-center">
        <div class="container">
            <span class="badge-soft d-inline-block mb-4" style="font-size: 20px;"><span class="bg-green"></span>Limited Time:
                Book Your Demo
                Today!</span>
            <h1 class="display-2 mb-3">Stop Juggling.<br><span class="text-green">Start Growing.</span></h1>
            <p class="mx-auto mb-4 text-muted-custom" style="max-width:720px">See how DOable transforms your studio, gym, or
                wellness business with enterprise-level tools that actually work for small businesses.</p>
            <div class="d-flex justify-content-center gap-4 flex-wrap mt-4 text-muted-custom small pb-4">
                <span>
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Circle -->
                        <circle cx="8" cy="8" r="7" stroke="#39b54a" stroke-width="2" />

                        <!-- Overlapping tick (extends beyond circle) -->
                        <path d="M4.2 8.7L6.6 11.1L12.6 5.2" stroke="#39b54a" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    30-Minute Live Demo</span>
                <span><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Circle -->
                        <circle cx="8" cy="8" r="7" stroke="#39b54a" stroke-width="2" />

                        <!-- Overlapping tick (extends beyond circle) -->
                        <path d="M4.2 8.7L6.6 11.1L12.6 5.2" stroke="#39b54a" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg> No Credit Card Required</span>
                <span><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Circle -->
                        <circle cx="8" cy="8" r="7" stroke="#39b54a" stroke-width="2" />

                        <!-- Overlapping tick (extends beyond circle) -->
                        <path d="M4.2 8.7L6.6 11.1L12.6 5.2" stroke="#39b54a" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg> Personalized Walkthrough</span>
            </div>
            <a class="btn btn-success btn-lg rounded-0 btn-gradient border-0" href="contact_us.php">Schedule Your Free Demo
                →</a>
        </div>
    </section>

    <section class="trusted-section text-center">
        <div class="container">

            <div class="trusted-title">
                Trusted by hundreds of service businesses
            </div>

            <div class="d-flex justify-content-center align-items-center trusted-list flex-wrap">
                <div class="trusted-item">Yoga Studios</div>
                <div class="trusted-divider"></div>
                <div class="trusted-item">Dance Schools</div>
                <div class="trusted-divider"></div>
                <div class="trusted-item">Fitness Centers</div>
                <div class="trusted-divider"></div>
                <div class="trusted-item">Martial Arts</div>
            </div>

        </div>
    </section>

    <!-- FEATURES -->
    <section>
        <div class="container">
            <div class="text-center mb-5">
                <h2>Everything You Need to <span class="text-green">Succeed</span></h2>
                <p class="text-muted-custom">See these powerful features in action during your personalized demo</p>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="border bg-green-light card p-4 h-100">
                        <div class="icon-box"><svg viewBox="0 0 24 24">
                                <rect x="3" y="4" width="18" height="18" rx="3" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg></div>
                        <h5>Smart Scheduling</h5>
                        <p class="text-muted-custom">Automated booking that syncs across all services and instructors.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border bg-green-light card p-4 h-100">
                        <div class="icon-box"><svg viewBox="0 0 24 24">
                                <circle cx="9" cy="8" r="3" />
                                <circle cx="17" cy="8" r="3" />
                                <path d="M2 20c0-3 3-5 7-5" />
                                <path d="M12 20c0-3 3-5 7-5" />
                            </svg></div>
                        <h5>Client Management</h5>
                        <p class="text-muted-custom">Track attendance, progress, and engagement in one dashboard.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border bg-green-light card p-4 h-100">
                        <div class="icon-box"><svg viewBox="0 0 24 24">
                                <rect x="2" y="5" width="20" height="14" rx="2" />
                                <line x1="2" y1="10" x2="22" y2="10" />
                            </svg></div>
                        <h5>Payments Made Easy</h5>
                        <p class="text-muted-custom">Accept payments, manage memberships, and automate billing.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border bg-green-light card p-4 h-100">
                        <div class="icon-box"><svg viewBox="0 0 24 24">
                                <path d="M3 17l6-6 4 4 7-7" />
                            </svg></div>
                        <h5>Growth Analytics</h5>
                        <p class="text-muted-custom">Real-time insights to grow your business and revenue.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border bg-green-light card p-4 h-100">
                        <div class="icon-box"><svg viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 7v5l3 3" />
                            </svg></div>
                        <h5>Save 15+ Hours/Week</h5>
                        <p class="text-muted-custom">Automate repetitive tasks and focus on teaching.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border bg-green-light card p-4 h-100">
                        <div class="icon-box"><svg viewBox="0 0 24 24">
                                <path d="M12 2l8 4v6c0 5-3.5 9-8 10-4.5-1-8-5-8-10V6z" />
                            </svg></div>
                        <h5>Enterprise Security</h5>
                        <p class="text-muted-custom">Bank-level security protecting business and client data.</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 text-center pt-4">
                    <a class="btn btn-success btn-lg rounded-0 btn-gradient border-0" href="contact_us.php">See it in Action -
                        Book Demo</a>
                </div>
            </div>
        </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="bg-soft">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Business Owners Like You <span class="text-green">Love DOable</span></h2>
                <p class="text-muted-custom">See what happened after their demo</p>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card p-4 h-100">
                        <div class="quotes-img">
                            <svg width="30" height="30" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="12" fill="#39b54a" />
                                <path
                                    d="M8.4 7.8C7.02 7.8 5.9 8.92 5.9 10.3c0 1.21.87 2.21 2.04 2.43-.09.48-.34.93-.72 1.31l.84.84c1.23-.75 2.14-2.03 2.14-3.52V7.8H8.4zm6.4 0c-1.38 0-2.5 1.12-2.5 2.5 0 1.21.87 2.21 2.04 2.43-.09.48-.34.93-.72 1.31l.84.84c1.23-.75 2.14-2.03 2.14-3.52V7.8h-1.8z"
                                    fill="#FFFFFF" />
                            </svg>
                        </div>
                        <div class="mb-2 color-star">★★★★★</div>
                        <p>“DOable gave me my evenings back.”</p>
                        <small class="text-dark font-600">Sarah Martinez</small>
                        <small class="text-muted">Owner</small>
                        <small class="text-success">Rhythm & Flow Dance Studio</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 h-100">
                        <div class="quotes-img">
                            <svg width="30" height="30" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="12" fill="#39b54a" />
                                <path
                                    d="M8.4 7.8C7.02 7.8 5.9 8.92 5.9 10.3c0 1.21.87 2.21 2.04 2.43-.09.48-.34.93-.72 1.31l.84.84c1.23-.75 2.14-2.03 2.14-3.52V7.8H8.4zm6.4 0c-1.38 0-2.5 1.12-2.5 2.5 0 1.21.87 2.21 2.04 2.43-.09.48-.34.93-.72 1.31l.84.84c1.23-.75 2.14-2.03 2.14-3.52V7.8h-1.8z"
                                    fill="#FFFFFF" />
                            </svg>
                        </div>
                        <div class="mb-2 color-star">★★★★★</div>
                        <p>“Attendance increased 35% in one week.”</p>
                        <small class="text-dark font-600">Sarah Martinez</small>
                        <small class="text-muted">Owner</small>
                        <small class="text-success">Rhythm & Flow Dance Studio</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 h-100">
                        <div class="quotes-img">
                            <svg width="30" height="30" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="12" fill="#39b54a" />
                                <path
                                    d="M8.4 7.8C7.02 7.8 5.9 8.92 5.9 10.3c0 1.21.87 2.21 2.04 2.43-.09.48-.34.93-.72 1.31l.84.84c1.23-.75 2.14-2.03 2.14-3.52V7.8H8.4zm6.4 0c-1.38 0-2.5 1.12-2.5 2.5 0 1.21.87 2.21 2.04 2.43-.09.48-.34.93-.72 1.31l.84.84c1.23-.75 2.14-2.03 2.14-3.52V7.8h-1.8z"
                                    fill="#FFFFFF" />
                            </svg>
                        </div>
                        <div class="mb-2 color-star">★★★★★</div>
                        <p>“Saved $800/month on subscriptions.”</p>
                        <small class="text-dark font-600">Sarah Martinez</small>
                        <small class="text-muted">Owner</small>
                        <small class="text-success">Rhythm & Flow Dance Studio</small>
                    </div>
                </div>
            </div>

            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="card p-4">
                        <h3 class="text-green fw-bold">15+</h3>
                        <p class="mb-0">Hours Saved / Week</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4">
                        <h3 class="text-green fw-bold">98%</h3>
                        <p class="mb-0">Customer Satisfaction</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4">
                        <h3 class="text-green fw-bold">$800</h3>
                        <p class="mb-0">Average Monthly Savings</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA FORM -->
    <section>
        <div class="container">
            <div class="text-center mb-5">
                <h2>Let’s Get <span class="text-green">Started</span></h2>
                <p class="text-muted-custom">Ready to transform your business? Fill out the form and we’ll get back to you
                    within 24 hours.</p>
            </div>

            <div class="row g-5 align-items-start">
                <div class="col-md-6">
                    <h5>Get in Touch</h5>
                    <p class="text-muted-custom">Have questions about DOable or want to see it in action? Our team will guide you
                        through a personalized demo tailored to your business.</p>
                    <div class="contact-block">
                        <div class="contact-item">
                            <div class="icon-badge">
                                <!-- Mail Icon -->
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4 6H20V18H4V6Z" stroke="#39b54a" stroke-width="2" stroke-linejoin="round" />
                                    <path d="M4 7L12 13L20 7" stroke="#39b54a" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </div>

                            <div class="contact-text">
                                <div class="contact-title">Email Us</div>
                                <div class="contact-subtitle">demo@doable.net</div>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="icon-badge">
                                <!-- Location Icon -->
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 21C15.5 17.5 19 14.4 19 10A7 7 0 0 0 5 10C5 14.4 8.5 17.5 12 21Z" stroke="#39b54a"
                                        stroke-width="2" stroke-linejoin="round" />
                                    <circle cx="12" cy="10" r="2.5" stroke="#39b54a" stroke-width="2" />
                                </svg>
                            </div>

                            <div class="contact-text">
                                <div class="contact-title">Visit Us</div>
                                <div class="contact-subtitle">
                                    Serving small businesses nationwide
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-green-light border card p-4 mt-4">
                        <strong>What Happens Next?</strong>
                        <ol class="what-nos text-muted-custom mt-2 mb-0">
                            <li>We review your information within 24 hours</li>
                            <li>Schedule a personalized demo</li>
                            <li>Start your free trial with guided setup</li>
                        </ol>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-card">
                        <form>

                            <div class="form-group">
                                <label>Full Name <span>*</span></label>
                                <div class="input-wrapper">
                                    <span class="input-icon">👤</span>
                                    <input type="text" placeholder="Full Name" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email Address <span>*</span></label>
                                <div class="input-wrapper">
                                    <span class="input-icon">✉️</span>
                                    <input type="email" placeholder="Email Address" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Business Name <span>*</span></label>
                                <div class="input-wrapper">
                                    <span class="input-icon">🏢</span>
                                    <input type="text" placeholder="Business Name" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Business Type <span>*</span></label>
                                <div class="input-wrapper">
                                    <span class="input-icon">🏬</span>
                                    <select>
                                        <option value="">Select Business Type</option>
                                        <?php
                                        $row = $db->Execute("SELECT PK_BUSINESS_TYPE, BUSINESS_TYPE FROM DOA_BUSINESS_TYPE WHERE ACTIVE = 1");
                                        while (!$row->EOF) { ?>
                                            <option value="<?php echo $row->fields['PK_BUSINESS_TYPE']; ?>"><?= $row->fields['BUSINESS_TYPE'] ?></option>
                                        <?php
                                            $row->MoveNext();
                                        } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Phone Number <span>*</span></label>
                                <div class="input-wrapper">
                                    <span class="input-icon">📞</span>
                                    <input type="tel" placeholder="Phone Number" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Message <span>*</span></label>
                                <div class="input-wrapper textarea">
                                    <span class="input-icon">💬</span>
                                    <textarea rows="4"
                                        placeholder="Tell us about your business and what you're looking for..."></textarea>
                                </div>
                            </div>

                            <div class="consent-box">
                                <input type="checkbox" />
                                <p>
                                    I agree to receive text messages from Doable related to service updates and support communications.
                                    Message frequency may vary. Message & data rates may apply. Reply STOP to opt out or HELP for help.
                                    View our <a href="terms_of_use.php">Terms of Use</a> and <a href="privacy_policy.php">Privacy Policy</a>.
                                </p>
                            </div>

                            <button class="submit-btn">
                                Send Message <span>➜</span>
                            </button>

                            <p class="form-footer">
                                By submitting this form, you agree to be contacted by our team.
                                We respect your privacy and will never share your information.
                            </p>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-secondary py-4">
        <div class="container dd-flex jjustify-content-between fflex-wrap">
            <div class="row">
                <div class="col-md-4">
                    <h3 class="text-success mb-0">
                        DOable.net
                    </h3>
                    <small>Enterprise-level business management for small service businesses.</small>
                </div>
                <div class="col-md-4">
                    <h5 class="text-success text-center mt-4">
                        Anything is DOable
                    </h5>
                </div>
                <div class="col-md-4 text-right pt-2" style="text-align: right;">
                    <small class="mb-0">2025 DOable.net</small><br />
                    <small class="mb-0">Made with
                        <img src="demo1/images/heart.png" /> for small businesses</small>
                </div>
            </div>
            <!-- <div>© 2025 DOable.net</div>
    <div>Anything is DOable.</div> -->
        </div>
    </footer>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.5.0/js/fontawesome.min.js"></script>
</body>

</html>