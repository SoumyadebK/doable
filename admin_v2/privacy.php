<?php
require_once('../global/config.php');
// Optional: require login
// if (empty($_SESSION['PK_USER'])) { header("location:../login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy — Doable.net</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #39B54A;
            --primary-dark: #2D8F3B;
            --primary-rgb: 57, 181, 74;
            --primary-soft: #F0FDF4;
            --primary-soft-border: #A7F3D0;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--gray-50);
            margin: 0;
            color: var(--gray-700);
            line-height: 1.65;
            font-size: 14px;
        }

        .terms-doc {
            max-width: 900px;
            margin: 0 auto;
            padding: 32px 24px 40px;
        }

        /* Brand bar */
        .doc-brand-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--gray-300);
            font-size: 11.5px;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--gray-500);
            font-weight: 600;
        }

        .doc-brand-bar .left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .doc-brand-bar .left i {
            color: var(--primary-color);
            font-style: normal;
        }

        /* Hero — green gradient */
        .privacy-hero {
            background: linear-gradient(135deg, #F0FDF4 0%, #ECFDF5 100%);
            border: 1px solid var(--primary-soft-border);
            border-radius: 16px;
            padding: 30px 32px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .05);
        }

        .privacy-hero .kicker {
            font-size: 12px;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .privacy-hero h1 {
            font-size: 26px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 10px;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .privacy-hero .meta {
            font-size: 12.5px;
            color: var(--gray-600);
            display: flex;
            flex-wrap: wrap;
            gap: 6px 10px;
            padding-bottom: 14px;
            margin-bottom: 16px;
            border-bottom: 1px dashed var(--primary-soft-border);
        }

        .privacy-hero .meta strong {
            color: var(--gray-800);
        }

        .privacy-hero .meta .sep {
            color: var(--gray-300);
        }

        .privacy-hero .company-identity {
            font-size: 12.5px;
            color: var(--gray-600);
            line-height: 1.65;
        }

        .privacy-hero .company-identity strong {
            color: var(--gray-800);
        }

        .privacy-hero .company-identity .dot {
            color: var(--gray-300);
            margin: 0 6px;
        }

        /* Sticky section nav */
        .privacy-nav {
            position: sticky;
            top: 0;
            z-index: 5;
            background: rgba(249, 250, 251, .92);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--gray-200);
            margin: 0 -24px 20px;
            padding: 8px 24px;
            overflow-x: auto;
            white-space: nowrap;
            scrollbar-width: thin;
        }

        .privacy-nav a {
            display: inline-block;
            padding: 6px 12px;
            margin-right: 4px;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-600);
            text-decoration: none;
            border-radius: 999px;
            transition: all .15s;
        }

        .privacy-nav a:hover {
            background: var(--primary-soft);
            color: var(--primary-dark);
        }

        .privacy-nav::-webkit-scrollbar {
            height: 6px;
        }

        .privacy-nav::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 6px;
        }

        /* Callouts */
        .privacy-callout {
            border-radius: 12px;
            padding: 16px 20px;
            margin: 20px 0;
            font-size: 13.5px;
            line-height: 1.6;
        }

        .privacy-callout.indigo {
            background: var(--primary-soft);
            border: 1px solid var(--primary-soft-border);
            color: var(--gray-800);
        }

        .privacy-callout.teal {
            background: #ECFEFF;
            border: 1px solid #A5F3FC;
            color: #155E75;
        }

        .privacy-callout strong {
            font-weight: 700;
            display: block;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .03em;
            font-size: 12.5px;
        }

        /* Sections */
        .terms-section {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 24px 28px;
            margin-bottom: 16px;
            scroll-margin-top: 60px;
        }

        .terms-section h2 {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .terms-section h2 .sec-num {
            background: var(--primary-color);
            color: #fff;
            font-size: 12px;
            min-width: 26px;
            height: 26px;
            border-radius: 50%;
            padding: 0 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-weight: 600;
            letter-spacing: 0;
        }

        .terms-section h3 {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--gray-800);
            margin: 16px 0 6px;
        }

        .terms-section h3:first-of-type {
            margin-top: 0;
        }

        .terms-section p {
            margin: 0 0 10px;
        }

        .terms-section p:last-child {
            margin-bottom: 0;
        }

        .terms-section ul,
        .terms-section ol {
            margin: 6px 0 12px;
            padding-left: 22px;
        }

        .terms-section li {
            margin-bottom: 6px;
        }

        /* Lettered lists */
        .terms-section ol.lettered {
            list-style: none;
            padding-left: 0;
            counter-reset: lettered;
        }

        .terms-section ol.lettered>li {
            counter-increment: lettered;
            position: relative;
            padding-left: 28px;
            margin-bottom: 8px;
        }

        .terms-section ol.lettered>li::before {
            content: "(" counter(lettered, lower-alpha) ")";
            position: absolute;
            left: 0;
            top: 0;
            color: var(--primary-dark);
            font-weight: 600;
            font-size: 13px;
        }

        /* Tables */
        .privacy-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            overflow: hidden;
            margin: 8px 0 14px;
        }

        .privacy-table thead th {
            background: var(--gray-50);
            color: var(--gray-600);
            padding: 10px 14px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--gray-200);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .privacy-table tbody td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--gray-100);
            color: var(--gray-700);
            vertical-align: top;
        }

        .privacy-table tbody tr:last-child td {
            border-bottom: none;
        }

        .privacy-table .cat-row td {
            background: var(--primary-soft);
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .02em;
            border-top: 1px solid var(--primary-soft-border);
            border-bottom: 1px solid var(--primary-soft-border);
        }

        /* Contact box */
        .contact-box {
            background: linear-gradient(135deg, #F0FDF4 0%, #ECFDF5 100%);
            border: 1px solid var(--primary-soft-border);
        }

        .contact-box h2 {
            color: var(--primary-dark);
            border-color: var(--primary-soft-border);
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-top: 10px;
        }

        .contact-grid .label {
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--gray-500);
            font-weight: 600;
            margin-bottom: 2px;
        }

        .contact-grid .value {
            font-size: 14px;
            color: var(--gray-800);
            font-weight: 500;
            word-break: break-word;
        }

        /* Acknowledgment */
        .acknowledgment {
            background: #111827;
            color: #F9FAFB;
            border-radius: 12px;
            padding: 24px 28px;
            margin-top: 24px;
        }

        .acknowledgment h2 {
            color: #fff;
            border-bottom: 1px solid #374151;
            display: flex;
        }

        .acknowledgment h2 .sec-num {
            background: var(--primary-color);
        }

        .acknowledgment p {
            color: #D1D5DB;
            font-size: 13px;
            margin: 0 0 10px;
        }

        .acknowledgment p:last-child {
            margin-bottom: 0;
        }

        .terms-doc-footer {
            text-align: center;
            font-size: 12px;
            color: var(--gray-500);
            padding-top: 24px;
            margin-top: 24px;
            border-top: 1px solid var(--gray-200);
        }

        .terms-doc-footer .page-num {
            color: var(--gray-400);
            margin-bottom: 4px;
        }

        @media (max-width:640px) {
            .terms-doc {
                padding: 20px 14px;
            }

            .privacy-hero {
                padding: 20px 22px;
            }

            .privacy-hero h1 {
                font-size: 20px;
            }

            .terms-section {
                padding: 20px 20px;
            }

            .terms-section h2 {
                font-size: 14px;
            }

            .privacy-nav {
                margin: 0 -14px 16px;
                padding: 8px 14px;
            }
        }
    </style>
</head>

<body>
    <article class="terms-doc">

        <!-- Brand bar -->
        <div class="doc-brand-bar">
            <div class="left"><i>◆</i> Doable.net</div>
            <div>Privacy Policy</div>
        </div>

        <!-- Hero -->
        <header class="privacy-hero">
            <div class="kicker">Doable.net</div>
            <h1>Privacy Policy</h1>
            <div class="meta">
                <span><strong>Effective:</strong> October 1, 2026</span>
                <span class="sep">|</span>
                <span><strong>Last Updated:</strong> October 1, 2026</span>
            </div>
            <div class="company-identity">
                <strong>Doable LLC</strong>, a Wyoming limited liability company (d/b/a <strong>Doable.net</strong>)
                <span class="dot">•</span> 1712 Pioneer Ave., Suite 783, Cheyenne, Wyoming 82001
                <span class="dot">•</span> Info@doable.net
            </div>
            <p style="margin:16px 0 0; font-size:13px; color:var(--gray-600);">
                This Privacy Policy describes how Doable LLC ("Doable," "we," "us," "our") collects, uses, discloses,
                retains, and protects Personal Information through doable.net, our web application, mobile applications,
                scheduling pages and widgets, SMS and email notifications, and related services (the "Services").
            </p>
        </header>

        <!-- Sticky nav -->
        <nav class="privacy-nav" aria-label="Privacy sections">
            <a href="#s1">Scope</a>
            <a href="#s2">Information We Collect</a>
            <a href="#s3">How We Use</a>
            <a href="#s4">How We Disclose</a>
            <a href="#s5">Cookies</a>
            <a href="#s6">Retention</a>
            <a href="#s7">Security</a>
            <a href="#s8">US Rights</a>
            <a href="#s9">Canada Rights</a>
            <a href="#s10">Children</a>
            <a href="#s11">Communications</a>
            <a href="#s12">International</a>
            <a href="#s13">Enterprise &amp; DPA</a>
            <a href="#s14">Third-Party</a>
            <a href="#s15">Changes</a>
            <a href="#s16">Disputes</a>
            <a href="#s17">Contact</a>
            <a href="#s18">Cross-Reference</a>
        </nav>

        <!-- SECTION 1 -->
        <section class="terms-section" id="s1">
            <h2><span class="sec-num">1</span> Introduction and Scope</h2>
            <h3>1.1</h3>
            <p>This Privacy Policy describes how Doable LLC ("Doable," "we," "us," "our") collects, uses, discloses, retains and protects Personal Information through doable.net, our web application, mobile applications, scheduling pages and widgets, SMS and email notifications, and related services (the "Services").</p>
            <h3>1.2</h3>
            <p>This Policy is incorporated into the Terms of Use and Service Agreement at https://doable.net/terms (the "Terms"). Capitalized terms not defined here have the meanings in the Terms (including "Personal Information" (Terms §1.12), "User Data" (Terms §1.16), "Enterprise Customer" (Terms §1.8), "DPA" (Terms §1.6) and "Canadian User" (Terms §1.5)). Consistent with Terms §10.1, if this Policy conflicts with the Terms on the handling of Personal Information, this Policy controls; on all other matters, including dispute resolution, limitation of liability and governing law, the Terms control.</p>
            <h3>1.3</h3>
            <p>This Policy applies to (a) "Account Holders" (people or organizations who register for the Services); (b) "Invitees" (people who book, receive or are listed on a meeting or appointment created through the Services by an Account Holder); and (c) "Visitors" (people who visit our websites or contact us).</p>
            <h3>1.4 Two roles.</h3>
            <p>For our own website, billing, account administration, security, support and marketing, Doable acts as the business / controller. For User Data (Terms §1.16)—the scheduling content, Invitee details, notes and attachments that Account Holders and Invitees put into the Services—Doable acts as a service provider / processor on behalf of the Account Holder, who decides why and how that data is used. If you are an Invitee, the Account Holder who scheduled with you is responsible for its own use of your information and its own privacy notice; send requests about that information to that Account Holder, or to us and we will help route them as described in Section 8.7.</p>
            <h3>1.5 Geographic scope.</h3>
            <p>The Services are offered to businesses and individuals in the United States and Canada. The Services are not directed to, and we do not market to, individuals located in the European Economic Area, the United Kingdom or Switzerland. Section 12 explains how we treat any such person who nonetheless uses the Services.</p>
            <h3>1.6 Eligibility.</h3>
            <p>The Services are for people 18 or older (Terms §2). See Section 10.</p>
            <h3>1.7</h3>
            <p>By using the Services you acknowledge this Policy. If you do not agree, do not use the Services.</p>
        </section>

        <!-- SECTION 2 -->
        <section class="terms-section" id="s2">
            <h2><span class="sec-num">2</span> Information We Collect</h2>
            <p>2.1 – 2.3 The table below describes the Personal Information we collect, organized as (2.1) information you provide, (2.2) information collected automatically, and (2.3) information from third parties, which we receive only with your authorization.</p>
            <table class="privacy-table">
                <thead>
                    <tr>
                        <th style="width:22%">Category</th>
                        <th>Examples</th>
                        <th style="width:22%">Source</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="cat-row">
                        <td colspan="3">2.1 Information you provide</td>
                    </tr>
                    <tr>
                        <td><strong>Identifiers and account information</strong></td>
                        <td>Name, email, password (stored hashed), time zone, organization, role, profile photo, booking page URL</td>
                        <td>From you</td>
                    </tr>
                    <tr>
                        <td><strong>Scheduling information</strong></td>
                        <td>Event types, availability, meeting dates and times, Invitee names, emails, phone numbers, answers to booking questions, notes, attachments, locations, video links</td>
                        <td>From Account Holders and Invitees</td>
                    </tr>
                    <tr class="cat-row">
                        <td colspan="3">2.2 Information collected automatically</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="font-style:italic; color:var(--gray-500); background:#F9FAFB;">
                            Automatically collected information is described in Section 2.6 and Section 5 below.
                        </td>
                    </tr>
                    <tr class="cat-row">
                        <td colspan="3">2.3 Information from third parties (only with your authorization)</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="font-style:italic; color:var(--gray-500); background:#F9FAFB;">
                            Information received from third parties is described in Section 13 and Section 14 below.
                        </td>
                    </tr>
                </tbody>
            </table>

            <h3>2.4 Inferences.</h3>
            <p>We derive limited internal metrics (for example, feature usage and account activity levels) to operate and improve the Services. We do not build advertising profiles.</p>

            <h3>2.5 Sensitive information.</h3>
            <p>The Services are not designed for sensitive personal information (Terms §9.3), such as government identifiers, financial account numbers, health or medical information, biometric data, precise geolocation, racial or ethnic origin, religious beliefs, sexual orientation, or information about children. We do not request it and do not use it to infer characteristics. A free-text booking field or attachment could contain such information if an Account Holder or Invitee chooses to enter it; we treat it as User Data held on that person's behalf and use it for no purpose other than providing the Services.</p>

            <h3>2.6</h3>
            <p>We do not collect biometric identifiers or precise geolocation. Our mobile applications may request permission for your calendar, contacts, notifications and camera or photo library only to provide features you invoke, and you can revoke these in your device settings.</p>
        </section>

        <!-- SECTION 3 -->
        <section class="terms-section" id="s3">
            <h2><span class="sec-num">3</span> How We Use Personal Information</h2>
            <h3>3.1</h3>
            <p>We use Personal Information to:</p>
            <ol class="lettered">
                <li>provide, operate, maintain and support the Services, including creating accounts, scheduling, sending confirmations, reminders and cancellations by email and SMS, syncing calendars, and creating video-meeting links;</li>
                <li>process payments, manage subscriptions, renewals, taxes and invoices (Terms §§5–6);</li>
                <li>communicate with you about the Services, security, changes to the Terms or this Policy, and account matters (Terms §8.1);</li>
                <li>send marketing messages only as permitted by Terms §§8.2 and 8.4 and by law, with the ability to opt out at any time;</li>
                <li>maintain security, detect and prevent fraud, abuse and violations of the Acceptable Use Policy (Terms §11), and debug and repair errors;</li>
                <li>understand and improve the Services using aggregated or de-identified information;</li>
                <li>comply with law, respond to lawful requests, establish, exercise or defend legal claims, and enforce the Terms;</li>
                <li>carry out business transfers described in Section 4.1(e); and</li>
                <li>perform any other purpose for which you give consent or which we describe at the time of collection.</li>
            </ol>
            <h3>3.2</h3>
            <p>We do not use User Data for advertising, do not sell it, and do not use it to train generative artificial intelligence models offered to others. We do not use Personal Information for purposes materially different from those disclosed here without notice and, where required, consent. Terms §9.2 grants Doable only the limited license to process User Data needed to provide and secure the Services.</p>
            <h3>3.3 Legal bases</h3>
            <p>(for Canadian users and any other person to whom a legal-basis requirement applies): consent (express or implied as permitted by law), performance of our contract with you, our legitimate business interests (security, fraud prevention, service improvement) not overridden by your rights, and compliance with legal obligations.</p>
            <h3>3.4 Automated decision-making.</h3>
            <p>We do not make decisions that produce legal or similarly significant effects about individuals using solely automated processing or profiling. If that changes, we will update this Policy, give required notice and offer any required opt-out or access rights beforehand.</p>
            <h3>3.5 HIPAA — healthcare providers.</h3>
            <p>The Services are not designed, certified or marketed for compliance with the Health Insurance Portability and Accountability Act of 1996, as amended ("HIPAA"). If you are a "covered entity" or "business associate" as defined by HIPAA and you intend to use the Services to process, transmit or store "protected health information" (PHI) as defined by HIPAA, you must contact us before doing so. Doable does not enter into HIPAA Business Associate Agreements as part of its standard Services; a separate written addendum signed by both parties is required and may not be available for all service tiers. Use of the Services to process PHI without such a signed addendum is prohibited by Terms §11. Doable bears no liability for any HIPAA violation arising from a user's choice to input PHI into the Services without a signed addendum.</p>
        </section>

        <!-- SECTION 4 -->
        <section class="terms-section" id="s4">
            <h2><span class="sec-num">4</span> How We Disclose Personal Information</h2>
            <h3>4.1</h3>
            <p>We disclose Personal Information only as follows:</p>
            <ol class="lettered">
                <li><strong>Service providers and subprocessors</strong> acting on our behalf under written contracts that restrict their use to providing services to us and require appropriate security: cloud hosting and storage; payment processing; email and SMS delivery; customer support tools; error monitoring and security services; and analytics limited to operating our own site and Services. A current list of subprocessors is available by emailing Info@doable.net, Subject: Subprocessor List Request.</li>
                <li><strong>The Account Holder</strong> with whom you schedule (for Invitees) and the Account Holder's authorized administrators (for members of an Enterprise Customer's organization).</li>
                <li><strong>Integration providers</strong> you choose to connect (Terms §13), at your direction, which then handle data under their own privacy policies.</li>
                <li><strong>Legal and safety:</strong> to comply with law, subpoena, court order or lawful government request, to enforce the Terms, to protect the rights, property or safety of Doable, our users or others, and to detect or prevent fraud; where legally permitted we will make reasonable efforts to notify the affected Account Holder of a request for User Data so they can object.</li>
                <li><strong>Business transfers:</strong> in a merger, acquisition, financing, reorganization, bankruptcy or sale of assets, in which case the successor must honor this Policy for previously collected Personal Information or give you notice and any required choice.</li>
                <li><strong>With your consent</strong> or at your direction.</li>
            </ol>
            <h3>4.2</h3>
            <p>We do not sell Personal Information or User Data and do not rent it. We do not "share" Personal Information for cross-context behavioral advertising or targeted advertising, as those terms are defined by California and other state privacy laws. We do not knowingly sell or share the Personal Information of anyone under 18. We have not sold or shared Personal Information in the preceding 12 months.</p>
            <h3>4.3</h3>
            <p>We may use and disclose aggregated or de-identified information that cannot reasonably be used to identify you. We maintain it in de-identified form and will not attempt to re-identify it.</p>
            <h3>4.4 Categories disclosed for a business purpose in the preceding 12 months (California categories):</h3>
            <p>identifiers; customer records (name, contact, billing); commercial information (subscription and plan); internet or other electronic network activity; geolocation (approximate, from IP); professional information (organization, role); and inferences limited to Service operation — disclosed to the service providers, Account Holders and legal authorities described in this Section.</p>
        </section>

        <!-- SECTION 5 -->
        <section class="terms-section" id="s5">
            <h2><span class="sec-num">5</span> Cookies and Similar Technologies</h2>
            <h3>5.1</h3>
            <p>We use (a) strictly necessary cookies for login, session, security and load balancing; (b) preference cookies to remember settings; and (c) limited analytics and performance technologies to understand how the Services are used and to fix errors. We do not use third-party advertising cookies or tracking pixels on the Services and do not permit advertising networks to collect data from the Services.</p>
            <h3>5.2</h3>
            <p>You can block or delete cookies in your browser settings, but strictly necessary cookies are required for the Services to work. Where required by law (including in Quebec), non-essential technologies are used only with your consent, which you can give or withdraw through the cookie settings link on our site or by emailing Info@doable.net, Subject: Cookie Preference Request.</p>
            <h3>5.3 Global Privacy Control and Do Not Track.</h3>
            <p>We treat a browser-based Global Privacy Control (GPC) signal as a valid request to opt out of sale and sharing under applicable state law. Because we do not sell or share Personal Information, no change in practice results. Our sites do not respond to other "Do Not Track" signals because there is no uniform standard.</p>
            <h3>5.4</h3>
            <p>We do not collect mobile advertising identifiers.</p>
        </section>

        <!-- SECTION 6 -->
        <section class="terms-section" id="s6">
            <h2><span class="sec-num">6</span> Retention and Deletion</h2>
            <h3>6.1</h3>
            <p>We keep Personal Information for as long as your account is active and as needed to provide the Services.</p>
            <h3>6.2 After termination or expiration.</h3>
            <p>Consistent with Terms §§10.5 and 20, User Data remains available for export for 90 days after termination or expiration of your account. Request an export by emailing Info@doable.net, Subject: Data Export Request. After that 90-day period we delete or irreversibly de-identify User Data from active systems, and from backups within a further 35 days in the ordinary course of rotation, unless a longer period is required by law or needed to resolve a dispute or enforce the Terms. <em>Note: closing or canceling your account through the account settings interface is not the same as, and does not automatically constitute, a deletion request under applicable privacy law; to exercise a legal right to deletion, use the request process in Section 8.2.</em></p>
            <h3>6.3 Records kept longer:</h3>
            <p>billing, tax and transaction records (generally up to seven years); security and audit logs (generally up to 12 months); records of privacy requests, consents, opt-outs and confidentiality</p>
            <h3>6.4</h3>
            <p>If you opt out of marketing, we keep your email address on a suppression list so we honor your choice.</p>
            <h3>6.5</h3>
            <p>Enterprise Customers may agree other retention terms in a DPA.</p>
        </section>

        <!-- SECTION 7 -->
        <section class="terms-section" id="s7">
            <h2><span class="sec-num">7</span> Security</h2>
            <h3>7.1</h3>
            <p>We maintain administrative, technical and physical safeguards appropriate to the sensitivity of the information, including encryption in transit and at rest (Terms §9.6), access controls and least-privilege access, authentication controls, logging and monitoring, vendor diligence, and personnel confidentiality obligations.</p>
            <h3>7.2</h3>
            <p>No method of transmission or storage is completely secure and we cannot guarantee absolute security. You are responsible for choosing a strong password, enabling available multi-factor authentication, and keeping credentials confidential (Terms §3). Report suspected unauthorized access immediately at Info@doable.net, Subject: Account Security — Unauthorized Access.</p>
            <h3>7.3 Breach notification.</h3>
            <p>If we determine that a security incident has compromised Personal Information, we will notify affected Account Holders and, where required, individuals and regulators without unreasonable delay and as required by applicable law, including state breach laws and, in Canada, PIPEDA (report to the Office of the Privacy Commissioner of Canada and notice to individuals where there is a real risk of significant harm) and Quebec Law 25 (reporting of confidentiality incidents presenting a risk of serious injury to the Commission d'accès à l'information du Québec ("CAI") and affected persons). We keep a register of incidents.</p>
        </section>

        <!-- SECTION 8 -->
        <section class="terms-section" id="s8">
            <h2><span class="sec-num">8</span> Your Privacy Rights — United States</h2>
            <h3>8.1</h3>
            <p>Depending on where you live, you may have the following rights, subject to verification and legal exceptions: to know and access the categories and specific pieces of Personal Information we hold; to correct inaccurate information; to delete; to obtain a portable copy; to opt out of sale, sharing, targeted advertising and certain profiling (we do none of these); to limit use of sensitive Personal Information (we do not use it beyond providing the Services); to receive a list of the categories of third parties to whom we have disclosed Personal Information; and to not be discriminated against for exercising these rights. These rights are provided under the California Consumer Privacy Act as amended by the California Privacy Rights Act (CCPA/CPRA) and the comprehensive privacy laws of other states, including Virginia, Colorado, Connecticut, Utah, Texas, Florida (for qualifying businesses), Oregon, Montana, Delaware, Iowa, Nebraska, New Hampshire, New Jersey, Tennessee, Indiana, Minnesota, Maryland, Rhode Island, Kentucky, and each other state whose comprehensive privacy law applies to us as it takes effect. To the extent permitted by law, we apply these rights to any U.S. resident who asks, regardless of state.</p>
            <h3>8.2 How to submit a request.</h3>
            <p>Email Info@doable.net, Subject: Privacy Rights Request — [Your State]. State the right you wish to exercise. We verify identity by matching information you provide to information we hold, and may ask for more if needed. An authorized agent may submit on your behalf with written permission; we may verify the agent and you directly. You need not have an account and we do not charge for exercising your rights.</p>
            <h3>8.3 Response times.</h3>
            <p>We confirm receipt within 10 business days and respond within 45 days; we may extend once by 45 days where reasonably necessary and will tell you why.</p>
            <h3>8.4 Appeals.</h3>
            <p>If we decline your request, you may appeal by emailing Info@doable.net, Subject: Privacy Rights Appeal — [Your State]. We respond in writing within 45 days (60 days where state law allows). If your appeal is denied, you may contact your state attorney general or, in California, the California Privacy Protection Agency or Attorney General.</p>
            <h3>8.5 California.</h3>
            <ol class="lettered">
                <li><strong>Notice at collection:</strong> the categories we collect, purposes and retention periods are in Sections 2, 3 and 6.</li>
                <li>We do not sell or share Personal Information, so there is no "Do Not Sell or Share My Personal Information" link; you may still send an opt-out request or GPC signal.</li>
                <li><strong>Shine the Light (Cal. Civ. Code §1798.83):</strong> we do not disclose Personal Information to third parties for their direct marketing; California residents may email Info@doable.net, Subject: California Shine the Light Request.</li>
                <li>The Services are not for anyone under 18.</li>
                <li>We offer no financial incentives for data.</li>
            </ol>
            <h3>8.6 Nevada.</h3>
            <p>Nevada residents may request an opt-out of sale of covered information; we do not sell it. Email Info@doable.net, Subject: Nevada Opt-Out Request.</p>
            <h3>8.7 Invitee requests.</h3>
            <p>If your information was submitted by an Account Holder, we may direct your request to that Account Holder, who controls that data, and will notify you. We will assist the Account Holder to respond as the Terms and any DPA require.</p>
        </section>

        <!-- SECTION 9 -->
        <section class="terms-section" id="s9">
            <h2><span class="sec-num">9</span> Your Privacy Rights — Canada</h2>
            <h3>9.1 PIPEDA and provincial laws.</h3>
            <p>For Canadian Users (Terms §1.5), we handle Personal Information in accordance with the Personal Information Protection and Electronic Documents Act ("PIPEDA") and applicable provincial private-sector privacy laws (including those of Alberta, British Columbia and Quebec) and follow the ten fair information principles: accountability, identifying purposes, consent, limiting collection, limiting use, disclosure and retention, accuracy, safeguards, openness, individual access, and challenging compliance.</p>
            <h3>9.2 Consent.</h3>
            <p>We rely on your express consent where the information is sensitive or where you would not reasonably expect the use, and on implied consent otherwise. You may withdraw consent at any time, subject to legal or contractual restrictions and reasonable notice, though withdrawal may mean we can no longer provide the Services. Email Info@doable.net, Subject: PIPEDA Privacy Request.</p>
            <h3>9.3</h3>
            <p>Your rights: access to your Personal Information and information about its use and disclosure; correction; withdrawal of consent; and to challenge our compliance. We respond within 30 days, extendable as the law allows with notice to you.</p>
            <h3>9.4 Privacy Officer.</h3>
            <p>Doable has designated a Privacy Officer responsible for compliance with this Policy and Canadian privacy law. Reach the Privacy Officer by emailing Info@doable.net, Subject: PIPEDA Privacy Request (or Quebec Law 25 Privacy Request for Quebec residents), or by mail at Doable LLC, Attn: Privacy Officer, 1712 Pioneer Ave., Suite 783, Cheyenne, Wyoming 82001, USA.</p>
            <h3>9.5 Quebec (Law 25).</h3>
            <p>If you are in Quebec, in addition to the above:</p>
            <ol class="lettered">
                <li>the Privacy Officer's contact details are in 9.4;</li>
                <li>you may request access to, rectification of, and deletion of your Personal Information, and portability of computerized personal information in a structured, commonly used technological format;</li>
                <li>where we use technology that profiles or identifies you, we will inform you and give you the means to activate it; our default settings provide the highest level of confidentiality;</li>
                <li>we do not make decisions based exclusively on automated processing; if that changes, we will inform you and give you the right to know the personal information used and the principal factors, and to have the decision reviewed by a person;</li>
                <li>we conduct privacy impact assessments before communicating personal information outside Quebec, and communicate it only where the assessment shows it would receive adequate protection, under a written agreement;</li>
                <li>consent is manifest, free, informed, specific to its purposes and requested separately from other information in clear and simple language; and</li>
                <li>you may email Info@doable.net, Subject: Quebec Law 25 Privacy Request, and, if unsatisfied, file a complaint with the Commission d'accès à l'information du Québec (CAI), cai.gouv.qc.ca.</li>
            </ol>
            <h3>9.6 Transfers outside Canada / Quebec.</h3>
            <p>Personal Information is stored and processed in the United States by Doable and our service providers, and may be accessible to U.S. courts, law enforcement and national security authorities under U.S. law. Our providers are bound by contract to protect it comparably. You may ask about our practices regarding service providers outside Canada by emailing Info@doable.net, Subject: PIPEDA Privacy Request.</p>
            <h3>9.7 Complaints.</h3>
            <p>If we cannot resolve your concern, you may complain to the Office of the Privacy Commissioner of Canada (priv.gc.ca) or, as applicable, the Office of the Information and Privacy Commissioner of Alberta, the Office of the Information and Privacy Commissioner for British Columbia, or the CAI in Quebec.</p>
            <h3>9.8 CASL.</h3>
            <p>We send commercial electronic messages to Canadians only with consent (express, or implied as CASL permits), include our identification and contact information (Doable LLC, 1712 Pioneer Ave., Suite 783, Cheyenne, Wyoming 82001, Info@doable.net) and a working unsubscribe mechanism, and honor unsubscribe with 10 business days. To unsubscribe, use the link in any message or email Info@doable.net, Subject: Unsubscribe — Marketing Emails. To report a concern, email Info@doable.net, Subject: CASL Complaint or Spam Report. Transactional messages (confirmations, reminders, security and account notices) are not marketing.</p>
        </section>

        <!-- SECTION 10 -->
        <section class="terms-section" id="s10">
            <h2><span class="sec-num">10</span> Children</h2>
            <p>The Services are intended only for individuals 18 or older (Terms §2). We do not knowingly collect Personal Information from anyone under 18, and the Services are not directed to children. If you believe a person under 18 has provided Personal Information, email Info@doable.net, Subject: Minor Data Report, and we will promptly delete it.</p>
        </section>

        <!-- SECTION 11 -->
        <section class="terms-section" id="s11">
            <h2><span class="sec-num">11</span> Communications Choices (Email and SMS)</h2>
            <h3>11.1 Email.</h3>
            <p>You may opt out of marketing emails at any time using the unsubscribe link or by emailing Info@doable.net, Subject: Unsubscribe — Marketing Emails. We will still send transactional and legal notices.</p>
            <h3>11.2 SMS.</h3>
            <p>We send SMS appointment notices as described in Terms §8.3, only to numbers provided with consent. Message frequency varies with scheduled appointments; message and data rates may apply. Reply STOP to opt out or HELP for help, or email Info@doable.net, Subject: SMS Opt-Out Request. No mobile information or SMS consent is shared with third parties or affiliates for their marketing or promotional purposes.</p>
            <h3>11.3</h3>
            <p>Account Holders are responsible for obtaining the consents required by law (including TCPA and CASL) before using the Services to message Invitees, as stated in Terms §8.</p>
            <h3>11.4 Push notifications.</h3>
            <p>If you enable push notifications in our mobile application, we send you notifications related to your appointments, account activity, reminders and service updates. You can disable push notifications at any time in your device settings. We do not use push notifications for third-party advertising.</p>
        </section>

        <!-- SECTION 12 -->
        <section class="terms-section" id="s12">
            <h2><span class="sec-num">12</span> International Users and Data Transfers</h2>
            <h3>12.1</h3>
            <p>Doable is based in the United States, and Personal Information is processed and stored in the United States. By using the Services from Canada, you understand your information will be transferred to and processed in the United States, where privacy laws may differ from those of your home jurisdiction, as described in Section 9.6.</p>
            <h3>12.2</h3>
            <p>The Services are not offered to persons in the EEA, UK or Switzerland. If, despite this, we learn that we hold Personal Information of such a person, we will honor requests to access, correct or delete it, and will apply appropriate transfer safeguards (such as Standard Contractual Clauses) in a DPA when contractually agreed with an Enterprise Customer.</p>
        </section>

        <!-- SECTION 13 -->
        <section class="terms-section" id="s13">
            <h2><span class="sec-num">13</span> Enterprise Customers and Data Processing Addendum</h2>
            <h3>13.1</h3>
            <p>Enterprise Customers (Terms §1.8) may request our Data Processing Addendum by emailing Info@doable.net, Subject: Data Processing Addendum Request.</p>
            <h3>13.2</h3>
            <p>Under a DPA, Doable acts as a processor / service provider and will: process User Data only on the Enterprise Customer's documented instructions and for the limited business purposes of providing the Services; not sell or share it, nor retain, use or disclose it outside the direct business relationship or for any other purpose; bind personnel to confidentiality; flow down the same obligations to subprocessors and remain responsible for them; assist with data subject requests and impact assessments; notify the customer of a breach affecting its data without undue delay; make available information needed to demonstrate compliance and permit reasonable audits as the DPA provides; and delete or return User Data at the end of the Services (subject to the 90-day period in Section 6.2). Once signed, the DPA controls over this Policy for the processing it covers.</p>
        </section>

        <!-- SECTION 14 -->
        <section class="terms-section" id="s14">
            <h2><span class="sec-num">14</span> Third-Party Services and Links</h2>
            <p>The Services may link to or integrate with third-party products (Terms §13), including Google Calendar, Microsoft Outlook, Zoom and Microsoft Teams. Their handling of information is governed by their own policies, and we are not responsible for them. Doable's use and transfer of information received from Google APIs will adhere to the Google API Services User Data Policy, including its Limited Use requirements: we use Google user data only to provide user-facing calendar and scheduling features, do not transfer it except to provide those features, comply with law, or as part of a merger or acquisition, do not use it for advertising, and do not allow humans to read it except with your consent, for security or abuse investigation, to comply with law, or on aggregated and anonymized data. You can revoke an integration at any time in the third party's account settings or in your Doable account, and we will delete the associated tokens. The Services and our emails may contain hyperlinks to third-party websites for your convenience. This Policy does not apply to those third-party sites and Doable is not responsible for their privacy practices; review the privacy policy of any third-party site before providing information to it.</p>
        </section>

        <!-- SECTION 15 -->
        <section class="terms-section" id="s15">
            <h2><span class="sec-num">15</span> Changes to This Policy</h2>
            <p>We may update this Policy. For material changes, consistent with Terms §22, we will give at least 30 days' notice by email to the address on your account and/or a prominent notice in the Services before the change takes effect; for non-material changes we will post the updated Policy with a new "Last Updated" date at least 14 days before it takes effect. Where law requires your consent to a change, we will ask for it. Continued use after the effective date is acceptance, except where consent is required; if you do not agree, you may cancel under Terms §§6 and 20. Prior versions are available on request.</p>
        </section>

        <!-- SECTION 16 -->
        <section class="terms-section" id="s16">
            <h2><span class="sec-num">16</span> Disputes: Governing Law</h2>
            <p>Any dispute about this Policy or our privacy practices is subject to Terms §18 (binding individual arbitration, class action waiver and 60-day opt-out) and the governing law in Terms §19 (Wyoming; Ontario for Canadian Users outside Quebec; Quebec law for Quebec residents). Nothing in this Policy limits any right you have to complain to a privacy regulator or to bring any claim that cannot be waived by law, and mandatory consumer or privacy rights under your local law are not reduced. Terms §§16 and 17 apply to the extent permitted by law.</p>
        </section>

        <!-- SECTION 17 -->
        <section class="terms-section contact-box" id="s17">
            <h2><span class="sec-num">17</span> How to Contact Us</h2>
            <p>Send every privacy request to Info@doable.net using the Subject line listed for your topic below.</p>
            <table class="privacy-table">
                <thead>
                    <tr>
                        <th style="width:45%">Topic</th>
                        <th>Email Subject Line (send to Info@doable.net)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>General Privacy Question</td>
                        <td>Privacy Question</td>
                    </tr>
                    <tr>
                        <td>US Privacy Rights Request</td>
                        <td>Privacy Rights Request — [Your State]</td>
                    </tr>
                    <tr>
                        <td>Appeal</td>
                        <td>Privacy Rights Appeal — [Your State]</td>
                    </tr>
                    <tr>
                        <td>PIPEDA Request / Complaint</td>
                        <td>PIPEDA Privacy Request</td>
                    </tr>
                    <tr>
                        <td>Quebec Law 25</td>
                        <td>Quebec Law 25 Privacy Request</td>
                    </tr>
                    <tr>
                        <td>Data Export</td>
                        <td>Data Export Request</td>
                    </tr>
                    <tr>
                        <td>Data Processing Addendum (DPA)</td>
                        <td>Data Processing Addendum Request</td>
                    </tr>
                    <tr>
                        <td>Subprocessor List</td>
                        <td>Subprocessor List Request</td>
                    </tr>
                    <tr>
                        <td>Security</td>
                        <td>Account Security — Unauthorized Access</td>
                    </tr>
                    <tr>
                        <td>Marketing Opt-out</td>
                        <td>Unsubscribe — Marketing Emails</td>
                    </tr>
                    <tr>
                        <td>SMS Opt-out</td>
                        <td>SMS Opt-Out Request</td>
                    </tr>
                    <tr>
                        <td>CASL</td>
                        <td>CASL Complaint or Spam Report</td>
                    </tr>
                    <tr>
                        <td>Cookie Preferences</td>
                        <td>Cookie Preference Request</td>
                    </tr>
                    <tr>
                        <td>California Shine the Light</td>
                        <td>California Shine the Light Request</td>
                    </tr>
                    <tr>
                        <td>Nevada Opt-Out</td>
                        <td>Nevada Opt-Out Request</td>
                    </tr>
                    <tr>
                        <td>Minor Data Report</td>
                        <td>Minor Data Report</td>
                    </tr>
                    <tr>
                        <td>Accessibility</td>
                        <td>Accessibility Accommodation Request</td>
                    </tr>
                    <tr>
                        <td>Legal Notice</td>
                        <td>Legal Notice</td>
                    </tr>
                </tbody>
            </table>
            <div class="contact-grid" style="margin-top:14px;">
                <div>
                    <div class="label">Mail</div>
                    <div class="value">Doable LLC, Attn: Privacy Officer, 1712 Pioneer Ave., Suite 783, Cheyenne, Wyoming 82001, USA</div>
                </div>
                <div>
                    <div class="label">Website</div>
                    <div class="value">https://doable.net</div>
                </div>
            </div>
            <p style="margin-top:14px; font-size:13px; color:var(--gray-600);">
                Please do not include passwords, payment card numbers or other sensitive information in emails.
            </p>
        </section>

        <!-- SECTION 18 -->
        <section class="terms-section" id="s18">
            <h2><span class="sec-num">18</span> Terms-to-Policy Cross-Reference</h2>
            <p>The table below maps each topic in this Policy to the corresponding provision of the Terms.</p>
            <table class="privacy-table">
                <thead>
                    <tr>
                        <th style="width:40%">Topic</th>
                        <th style="width:30%">This Policy</th>
                        <th style="width:30%">Terms</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Incorporation and priority</td>
                        <td>§1.2</td>
                        <td>§10.1</td>
                    </tr>
                    <tr>
                        <td>US privacy rights</td>
                        <td>§8</td>
                        <td>§10.2</td>
                    </tr>
                    <tr>
                        <td>PIPEDA</td>
                        <td>§9.1–9.4</td>
                        <td>§10.3</td>
                    </tr>
                    <tr>
                        <td>Quebec Law 25</td>
                        <td>§9.5</td>
                        <td>§10.4</td>
                    </tr>
                    <tr>
                        <td>Retention (90 days)</td>
                        <td>§6</td>
                        <td>§10.5</td>
                    </tr>
                    <tr>
                        <td>DPA / processor</td>
                        <td>§13</td>
                        <td>§10.6</td>
                    </tr>
                    <tr>
                        <td>No sale / advertising use</td>
                        <td>§3.2, §4.2</td>
                        <td>§9.4</td>
                    </tr>
                    <tr>
                        <td>Security</td>
                        <td>§7</td>
                        <td>§9.6</td>
                    </tr>
                    <tr>
                        <td>Sensitive data</td>
                        <td>§2.5</td>
                        <td>§9.3</td>
                    </tr>
                    <tr>
                        <td>Eligibility (18+)</td>
                        <td>§10</td>
                        <td>§2</td>
                    </tr>
                    <tr>
                        <td>Email / SMS / CASL</td>
                        <td>§9.8, §11</td>
                        <td>§8</td>
                    </tr>
                    <tr>
                        <td>Integrations</td>
                        <td>§14</td>
                        <td>§13.2</td>
                    </tr>
                    <tr>
                        <td>Disputes</td>
                        <td>§16</td>
                        <td>§18, §19</td>
                    </tr>
                    <tr>
                        <td>Changes</td>
                        <td>§15</td>
                        <td>§22</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Acknowledgment -->
        <section class="terms-section acknowledgment">
            <h2><span class="sec-num">✓</span> Acknowledgment</h2>
            <p>By using the Services you acknowledge that you have read and understood this Privacy Policy and consent to the collection, use, disclosure and retention practices described in it. If you do not agree, please do not use the Services. If you are accepting on behalf of a company or organization, you represent that you have authority to bind that entity to this Policy.</p>
            <p style="margin-top:16px; color:#9CA3AF; font-size:12px;">Doable LLC · 1712 Pioneer Ave., Suite 783 · Cheyenne, Wyoming 82001<br>Info@doable.net | doable.net · Privacy Policy — Version effective October 1, 2026 · Prior versions available on request.</p>
        </section>

        <div class="terms-doc-footer">
            <div class="page-num">© 2026 Doable LLC. All rights reserved.</div>
            <div>End of Privacy Policy</div>
        </div>

    </article>

    <script>
        /* Smooth-scroll for the sticky section nav */
        document.querySelectorAll('.privacy-nav a').forEach(a => {
            a.addEventListener('click', e => {
                const target = document.querySelector(a.getAttribute('href'));
                if (!target) return;
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });
        });
    </script>
</body>

</html>