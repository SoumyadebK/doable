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
    <title>Terms of Use &amp; Service Agreement — Doable.net</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #39B54A;
            --primary-dark: #2D8F3B;
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
            max-width: 880px;
            margin: 0 auto;
            padding: 32px 24px 40px;
        }

        /* Top brand bar */
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

        /* Header card */
        .terms-doc-header {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 28px 32px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .05);
        }

        .terms-doc-header .kicker {
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .terms-doc-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 10px;
            letter-spacing: -0.02em;
            line-height: 1.25;
        }

        .terms-doc-header .meta {
            font-size: 12.5px;
            color: var(--gray-500);
            display: flex;
            flex-wrap: wrap;
            gap: 6px 10px;
            padding-bottom: 14px;
            margin-bottom: 16px;
            border-bottom: 1px dashed var(--gray-200);
        }

        .terms-doc-header .meta strong {
            color: var(--gray-700);
            font-weight: 600;
        }

        .terms-doc-header .meta .sep {
            color: var(--gray-300);
        }

        /* Company identity line */
        .company-identity {
            font-size: 12.5px;
            color: var(--gray-600);
            line-height: 1.65;
        }

        .company-identity strong {
            color: var(--gray-800);
        }

        .company-identity .dot {
            color: var(--gray-300);
            margin: 0 6px;
        }

        /* Notices */
        .terms-notice {
            border-radius: 12px;
            padding: 16px 20px;
            margin: 20px 0;
            font-size: 13.5px;
            line-height: 1.6;
        }

        .terms-notice.important {
            background: #FEF2F2;
            border: 1px solid #FCA5A5;
            color: #7F1D1D;
        }

        .terms-notice.arbitration {
            background: #FFFBEB;
            border: 1px solid #FCD34D;
            color: #78350F;
        }

        .terms-notice.renewal {
            background: #EFF6FF;
            border: 1px solid #93C5FD;
            color: #1E3A8A;
        }

        .terms-notice strong {
            font-weight: 700;
            display: block;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .03em;
            font-size: 12.5px;
        }

        /* Sticky section nav */
        .terms-nav {
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

        .terms-nav a {
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

        .terms-nav a:hover {
            background: #F0FDF4;
            color: var(--primary-dark);
        }

        .terms-nav::-webkit-scrollbar {
            height: 6px;
        }

        .terms-nav::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 6px;
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
            width: 26px;
            height: 26px;
            border-radius: 50%;
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

        /* Definitions */
        .def-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .def-list li {
            padding: 9px 0;
            border-bottom: 1px solid var(--gray-100);
            font-size: 13.5px;
        }

        .def-list li:last-child {
            border-bottom: none;
        }

        .def-list .term {
            font-weight: 600;
            color: var(--gray-800);
        }

        /* Contact box */
        .contact-box {
            background: linear-gradient(135deg, #F0FDF4 0%, #ECFDF5 100%);
            border: 1px solid #A7F3D0;
        }

        .contact-box h2 {
            color: var(--primary-dark);
            border-color: #A7F3D0;
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
            background: #10B981;
        }

        .acknowledgment p {
            color: #D1D5DB;
            font-size: 13px;
            margin: 0 0 10px;
        }

        .acknowledgment p:last-child {
            margin-bottom: 0;
        }

        /* Doc footer */
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

            .terms-doc-header {
                padding: 20px 22px;
            }

            .terms-doc-header h1 {
                font-size: 20px;
            }

            .terms-section {
                padding: 20px 20px;
            }

            .terms-section h2 {
                font-size: 14px;
            }

            .terms-nav {
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
            <div>Terms of Use and Service Agreement</div>
        </div>

        <!-- Header card -->
        <header class="terms-doc-header">
            <div class="kicker">Doable.net</div>
            <h1>Terms of Use and Service Agreement</h1>
            <div class="meta">
                <span><strong>Effective:</strong> October 1, 2026</span>
                <span class="sep">|</span>
                <span><strong>Last Revised:</strong> October 1, 2026</span>
            </div>

            <div class="company-identity">
                <strong>Doable LLC</strong>, a Wyoming limited liability company (d/b/a <strong>Doable.net</strong>)
                <span class="dot">•</span> 1712 Pioneer Ave., Suite 783, Cheyenne, Wyoming 82001
                <span class="dot">•</span> Info@doable.net
            </div>

            <div class="terms-notice important" style="margin-top:20px;">
                <strong>Important Notice — Binding Legal Agreement — Please Read Carefully Before Using the Services</strong>
                These Terms of Use and Service Agreement ("Terms" or "Agreement") constitute a legally binding
                and enforceable contract between you, individually or on behalf of the company or organization you
                represent ("User," "you," or "your"), and <strong>Doable LLC</strong>, a Wyoming limited liability company
                doing business as <strong>Doable.net</strong> ("Doable," "we," "our," or "us"). This Agreement governs all access
                to and use of Doable's scheduling platform, website located at doable.net, mobile applications
                (iOS and Android), application programming interfaces (APIs), and all related software, tools,
                features, content, and services (collectively, the "Services").<br><br>
                BY ACCESSING OR USING THE SERVICES IN ANY MANNER — INCLUDING BROWSING THE DOABLE.NET
                WEBSITE, CREATING AN ACCOUNT, DOWNLOADING THE DOABLE MOBILE APPLICATION, CLICKING "I AGREE,"
                "ACCEPT," OR A SIMILAR BUTTON, OR ALLOWING ANY AUTHORIZED USER TO ACCESS THE SERVICES
                UNDER YOUR ACCOUNT — YOU ACKNOWLEDGE THAT YOU HAVE READ THIS AGREEMENT IN ITS ENTIRETY,
                THAT YOU UNDERSTAND ITS TERMS, AND THAT YOU AGREE TO BE LEGALLY BOUND BY ALL OF THEM.
                IF YOU DO NOT AGREE TO EVERY PROVISION OF THIS AGREEMENT, YOU ARE NOT AUTHORIZED TO ACCESS
                OR USE THE SERVICES IN ANY CAPACITY AND YOU MUST IMMEDIATELY CEASE ANY ACCESS TO OR USE
                OF THE SERVICES.
            </div>

            <div class="terms-notice arbitration">
                <strong>⚠ Arbitration and Class Action Notice</strong>
                SECTION 18 OF THIS AGREEMENT CONTAINS PROVISIONS FOR MANDATORY BINDING ARBITRATION AND A
                WAIVER OF YOUR RIGHT TO PARTICIPATE IN CLASS-ACTION LAWSUITS OR CLASS-WIDE ARBITRATION.
                THESE PROVISIONS SUBSTANTIALLY AFFECT YOUR LEGAL RIGHTS. PLEASE READ SECTION 18 CAREFULLY.
                YOU HAVE THE RIGHT TO OPT OUT OF ARBITRATION WITHIN SIXTY (60) DAYS OF FIRST ACCEPTING
                THESE TERMS AS DESCRIBED IN SECTION 18.8.
            </div>

            <div class="terms-notice renewal">
                <strong>🔄 Auto-Renewal Notice</strong>
                YOUR PAID SUBSCRIPTION WILL AUTOMATICALLY RENEW AT THE END OF EACH SUBSCRIPTION PERIOD
                UNLESS YOU CANCEL AT LEAST SEVEN (7) DAYS BEFORE THE RENEWAL DATE. CANCELLATION
                INSTRUCTIONS ARE PROVIDED IN SECTION 6.
            </div>
        </header>

        <!-- Sticky section nav -->
        <nav class="terms-nav" aria-label="Terms sections">
            <a href="#s1">Definitions</a>
            <a href="#s2">Eligibility</a>
            <a href="#s3">Account Setup</a>
            <a href="#s4">License</a>
            <a href="#s5">Fees &amp; Payment</a>
            <a href="#s6">Auto-Renewal</a>
            <a href="#s7">Beta Features</a>
            <a href="#s8">Communications</a>
            <a href="#s9">User Data</a>
            <a href="#s10">Privacy</a>
            <a href="#s11">Acceptable Use</a>
            <a href="#s12">Intellectual Property</a>
            <a href="#s13">Third-Party</a>
            <a href="#s14">Availability</a>
            <a href="#s15">Warranties</a>
            <a href="#s16">Liability</a>
            <a href="#s17">Indemnification</a>
            <a href="#s18">Arbitration</a>
            <a href="#s19">Governing Law</a>
            <a href="#s20">Termination</a>
            <a href="#s21">Export Controls</a>
            <a href="#s22">Modifications</a>
            <a href="#s23">Force Majeure</a>
            <a href="#s24">General</a>
            <a href="#s25">Contact</a>
        </nav>

        <!-- SECTION 1 -->
        <section class="terms-section" id="s1">
            <h2><span class="sec-num">1</span> Definitions</h2>
            <ul class="def-list">
                <li><span class="term">1.1 "Account"</span> means the registered user account created by or on behalf of a User to access and use the Services.</li>
                <li><span class="term">1.2 "Agreement" or "Terms"</span> means this Terms of Use and Service Agreement, including all schedules, exhibits, and incorporated documents (including the Privacy Policy), as may be amended from time to time in accordance with Section 22.</li>
                <li><span class="term">1.3 "Authorized User"</span> means any individual employee, contractor, or agent of an Enterprise Customer who is authorized by that Enterprise Customer to access and use the Services under the Enterprise Customer's Account.</li>
                <li><span class="term">1.4 "Beta Features"</span> means any features, functions, or capabilities of the Services made available by Doable on a pre-release, beta, preview, or early-access basis, which may be labeled "Beta," "Preview," "Early Access," or similar terminology.</li>
                <li><span class="term">1.5 "Canadian User"</span> means any User whose primary place of business, residence, or access to the Services is within the territorial boundaries of Canada.</li>
                <li><span class="term">1.6 "Data Processing Addendum" or "DPA"</span> means a written data processing agreement executed between Doable and an Enterprise Customer governing the processing of personal information as required by applicable privacy laws.</li>
                <li><span class="term">1.7 "Doable Indemnitees"</span> means Doable LLC and its members, managers, officers, directors, employees, agents, contractors, successors, assigns, licensors, and any third-party service providers integrated with the Services.</li>
                <li><span class="term">1.8 "Enterprise Customer"</span> means any company, limited liability company, partnership, corporation, non-profit organization, or other legal entity that licenses the Services for use by its employees, contractors, clients, or affiliates.</li>
                <li><span class="term">1.9 "Feedback"</span> means any ideas, suggestions, recommendations, comments, or other information about the Services that you provide to Doable, whether solicited or unsolicited.</li>
                <li><span class="term">1.10 "Intellectual Property Rights"</span> means all patents, copyrights, trademarks, service marks, trade dress, trade secrets, moral rights, rights of publicity, database rights, and all other proprietary rights recognized under applicable law in any jurisdiction worldwide, whether registered or unregistered.</li>
                <li><span class="term">1.11 "Materials"</span> means all text, data, information, software, graphics, calendars, images, icons, layout elements, audio, video, and other content created, owned, or licensed by Doable and made available through the Services, excluding User Data.</li>
                <li><span class="term">1.12 "Personal Information"</span> means any information that identifies, relates to, describes, is capable of being associated with, or could reasonably be linked, directly or indirectly, with a particular individual, as defined under applicable privacy law in the User's jurisdiction.</li>
                <li><span class="term">1.13 "Services"</span> means the Doable scheduling and appointment platform accessible via doable.net, all related mobile applications (iOS and Android), APIs, integrations, software tools, dashboards, and all other products and services offered by Doable, including all updates, upgrades, modifications, and new versions thereof.</li>
                <li><span class="term">1.14 "Subscription Period"</span> means the duration of a paid Subscription Plan, as selected by User at the time of Account creation or renewal (e.g., monthly, annual).</li>
                <li><span class="term">1.15 "Subscription Plan"</span> means the specific pricing tier and feature set selected by User, as described on the pricing page at https://doable.net/pricing.</li>
                <li><span class="term">1.16 "User Data"</span> means all data, content, text, images, audio, video, files, and other information uploaded, posted, transmitted, stored, or otherwise made available through the Services by User or its Authorized Users, excluding anonymized or aggregated analytics data.</li>
            </ul>
        </section>

        <!-- SECTION 2 -->
        <section class="terms-section" id="s2">
            <h2><span class="sec-num">2</span> Eligibility and Acceptance</h2>
            <h3>2.1 Age Requirement</h3>
            <p>The Services are intended solely for individuals who are eighteen (18) years of age or older. By registering for an Account or using the Services in any manner, you represent and warrant that you are at least eighteen (18) years of age. You are solely responsible for ensuring that all Authorized Users and all individuals you engage with through the Services are at least eighteen (18) years of age. Doable reserves the right to terminate any Account upon discovery that the Account holder is or was under the age of eighteen (18).</p>
            <h3>2.2 Authority to Bind Organizations</h3>
            <p>If you are entering into this Agreement on behalf of any company, organization, partnership, or other legal entity, you represent and warrant that: (a) you have full legal authority to bind that entity to all obligations set forth in this Agreement; (b) you have the right to act as an authorized representative of that entity for purposes of this Agreement; (c) you have read and understood this Agreement; and (d) you agree to this Agreement on behalf of that entity. In any such case, "User" refers to that entity and all references to "you" shall be construed accordingly. If you do not have authority to bind the entity, you may not accept this Agreement on its behalf and you are individually liable for any obligations arising from your use of the Services.</p>
            <h3>2.3 Geographic Availability</h3>
            <p>Doable provides the Services primarily to users in the United States and Canada. Access from other jurisdictions is not prohibited, but Doable makes no representation that the Services comply with the laws of any jurisdiction other than the United States and Canada, and access from unauthorized jurisdictions is at User's own risk. Notwithstanding the foregoing, Doable reserves the right to restrict or block access from specific jurisdictions at its sole discretion.</p>
            <h3>2.4 Acceptance Mechanisms</h3>
            <p>Your acceptance of this Agreement is evidenced by any of the following: (a) clicking an "I Agree," "Accept," "Sign Up," "Create Account," or functionally similar button or checkbox; (b) creating an Account; (c) accessing or using the Services; or (d) permitting any Authorized User to access or use the Services under your Account. Each such act constitutes your legal acceptance of this Agreement on the date of that act.</p>
        </section>

        <!-- SECTION 3 -->
        <section class="terms-section" id="s3">
            <h2><span class="sec-num">3</span> Account Setup, Security, and Management</h2>
            <h3>3.1 Account Registration</h3>
            <p>To access most features of the Services, you must create an Account. During registration, you must provide accurate, complete, and current information, including your legal name, a valid email address, and any other information required by the registration process. You agree to maintain and promptly update your Account information to ensure it remains accurate, complete, and current at all times.</p>
            <h3>3.2 Account Security</h3>
            <p>You are solely and exclusively responsible for maintaining the confidentiality and security of your Account login credentials, including your username, password, and any multi-factor authentication credentials. You agree to: (a) create and maintain a strong, unique password for your Account that is not used for any other service; (b) enable multi-factor authentication if and when Doable makes it available; (c) not share, disclose, or transfer your Account credentials to any person who is not an Authorized User; (d) restrict access to your Account to Authorized Users only; (e) log out of your Account at the end of each session, particularly on shared or public devices; and (f) immediately notify Doable of any known or suspected unauthorized access to or use of your Account by emailing Info@doable.net (Subject: Account Security — Unauthorized Access). Doable shall have no liability for any loss, damage, or harm resulting from your failure to comply with these security obligations, including from any unauthorized access to your Account resulting from your negligence or failure to maintain adequate security measures.</p>
            <h3>3.3 Responsibility for Account Activity</h3>
            <p>You are fully responsible and liable for all activity that occurs under your Account and for all actions taken by your Authorized Users. You shall ensure that all Authorized Users comply with the terms of this Agreement and you shall be jointly and severally liable for any breach of this Agreement by any Authorized User.</p>
            <h3>3.4 Account Accuracy and Prohibited Registration Practices</h3>
            <p>Creating an Account with false, misleading, or inaccurate information constitutes a material breach of this Agreement. You may not: (a) create an Account on behalf of another individual without that individual's explicit prior authorization; (b) create multiple Accounts for the purpose of circumventing any limitation, suspension, or termination of a prior Account; or (c) use another person's identity or registration information without authorization.</p>
            <h3>3.5 Inactive Account Policy</h3>
            <p>Doable reserves the right to suspend, terminate, or archive Accounts that have been inactive for a continuous period of twelve (12) months or more. Prior to taking any such action, Doable will send a notice to the email address associated with the Account at least thirty (30) days in advance, providing the Account holder an opportunity to reactivate the Account or export their User Data. If no response is received within thirty (30) days of such notice, Doable may proceed with suspension, termination, or archiving without further notice or liability.</p>
        </section>

        <!-- SECTION 4 -->
        <section class="terms-section" id="s4">
            <h2><span class="sec-num">4</span> License Grant and Restrictions</h2>
            <h3>4.1 License Grant</h3>
            <p>Subject to User's full compliance with all terms of this Agreement, including the timely payment of all applicable fees, Doable hereby grants User a limited, revocable, non-exclusive, non-transferable, non-sublicensable right and license to access and use the Services during the applicable Subscription Period, solely for User's internal business scheduling operations or personal scheduling purposes. This license does not include the right to make the Services available to third parties as a service, platform, or tool for their benefit.</p>
            <h3>4.2 Scope of Mobile Application License</h3>
            <p>Subject to User's compliance with this Agreement, Doable grants User a limited, revocable, personal, non-exclusive, non-transferable license to download and use the Doable mobile application on a device owned or controlled by User, solely in accordance with the terms of this Agreement.</p>
            <h3>4.3 Restrictions on Use</h3>
            <p>User shall not, and shall ensure that each of its Authorized Users does not:</p>
            <ol class="lettered">
                <li>copy, reproduce, modify, adapt, translate, create derivative works of, or otherwise alter the Services, the Materials, or any component thereof, in whole or in part, for any purpose;</li>
                <li>rent, lease, lend, loan, sell, resell, sublicense, assign, transfer, distribute, publish, or otherwise make available the Services or Materials to any third party, including without limitation as part of any service bureau, outsourcing service, application service provider arrangement, managed service arrangement, or time-sharing arrangement, without Doable's prior written consent;</li>
                <li>reverse engineer, disassemble, decompile, decode, translate, reconstruct, or otherwise attempt to derive or access the source code, underlying algorithms, structure, or logic of the Services or any component thereof;</li>
                <li>remove, alter, obscure, or tamper with any copyright notices, trademark notices, proprietary legends, confidentiality notices, or other proprietary markings within or on the Services or Materials;</li>
                <li>use the Services or Materials in any manner that infringes, misappropriates, dilutes, or otherwise violates any Intellectual Property Rights or other rights of any third party in any jurisdiction, including the United States and Canada;</li>
                <li>use the Services to build, develop, or support any product or service that competes with the Services, or to benchmark the Services against any competitive product and publicly disclose the results of such benchmarking without Doable's prior written consent;</li>
                <li>access or use the Services through automated means, robots, bots, scrapers, crawlers, spiders, data mining tools, or other automated mechanisms except through Doable-approved APIs in accordance with Doable's published API documentation;</li>
                <li>circumvent, disable, bypass, or otherwise interfere with any technical protection measures, access controls, rate limits, or security features implemented by Doable in connection with the Services;</li>
                <li>use the Services to process, store, or transmit data or information in violation of any applicable law or regulation, including any applicable privacy, data protection, anti-spam, or export control law;</li>
                <li>use the Services in any manner that imposes an unreasonable or disproportionate burden on Doable's infrastructure or that interferes with or degrades the performance of the Services for other users; or</li>
                <li>use the Services in any way that violates any applicable local, state, provincial, federal, or international law, regulation, rule, or order.</li>
            </ol>
            <h3>4.4 Reservation of Rights</h3>
            <p>Except for the limited license expressly granted in Sections 4.1 and 4.2, all right, title, and interest in and to the Services, the Materials, and all Intellectual Property Rights therein are and shall remain exclusively owned by Doable and its licensors. No title to, or ownership of, any Intellectual Property Rights in the Services or Materials is transferred to User by this Agreement or by User's access to or use of the Services. All rights not expressly granted to User in this Agreement are expressly reserved to Doable and its licensors. No rights are granted by implication, estoppel, course of dealing, or otherwise.</p>
            <h3>4.5 No Implied License</h3>
            <p>Nothing in this Agreement shall be construed to grant User any license or right to use Doable's trade names, trademarks, service marks, logos, or other brand features except as expressly set forth in Section 12 of this Agreement.</p>
        </section>

        <!-- SECTION 5 -->
        <section class="terms-section" id="s5">
            <h2><span class="sec-num">5</span> Subscription Plans, Fees, and Payment</h2>
            <h3>5.1 Subscription Plans</h3>
            <p>Doable offers a variety of Subscription Plans for access to the Services, including free and paid tiers. The features, functionality, and pricing associated with each Subscription Plan are described on Doable's pricing page at https://doable.net/pricing, which is incorporated by reference into this Agreement. Doable reserves the right to modify the features and pricing of Subscription Plans at any time, subject to the notice requirements set forth in Section 5.7.</p>
            <h3>5.2 Payment Obligation</h3>
            <p>By selecting a paid Subscription Plan and providing valid payment information, you expressly authorize Doable and its designated third-party payment processors to charge the applicable subscription fees, taxes, and other charges to your designated payment method, at the intervals and in the amounts corresponding to your selected Subscription Plan, as set forth on the pricing page at the time of your selection.</p>
            <h3>5.3 Payment Methods and Processors</h3>
            <p>Doable accepts major credit cards, debit cards, and such other payment methods as Doable may make available from time to time. Payment processing is handled by third-party payment processors. Doable does not store complete payment card numbers. By providing your payment information, you represent and warrant that: (a) you are authorized to use the designated payment method; (b) the payment information you provide is true, accurate, and complete; and (c) you will promptly update your payment information if it changes. Doable has no liability to you arising out of the acts, omissions, errors, or failures of any third-party payment processor. Questions about payment processing should be directed to Info@doable.net (Subject: Billing Inquiry).</p>
            <h3>5.4 Taxes</h3>
            <p>All fees are stated exclusive of all applicable taxes unless expressly stated otherwise. User is solely responsible for all sales, use, value-added, goods and services, harmonized sales, provincial sales, excise, withholding, and other taxes (collectively, "Taxes") assessed in connection with your purchase or use of the Services, other than taxes assessed on Doable's net income. For Canadian Users, Doable will collect and remit applicable federal and provincial Taxes (GST/HST/QST) as required by Canadian tax law, and such Taxes will be added to your invoice. For Users in US states that impose sales or use taxes on SaaS services, Doable may collect and remit applicable Taxes. Where Doable is required by law to collect such Taxes, they will be added to your invoice.</p>
            <h3>5.5 Non-Refundable Fees</h3>
            <p>Except as expressly provided in Sections 5.8, 6.4, 22.3, and 23, all fees paid to Doable are non-refundable and non-creditable. This non-refundable policy applies to all fees charged during the Subscription Period, including any fees charged following automatic renewal. No refunds or credits will be issued for partial Subscription Periods, for periods during which User's Account was suspended for breach, or for any unused features of a Subscription Plan. User is responsible for promptly canceling its Subscription Plan if it no longer wishes to use the Services.</p>
            <h3>5.6 Free Accounts and Free Tiers</h3>
            <p>Doable may offer limited access to certain features of the Services free of charge ("Free Tier"). Use of the Free Tier is subject to this Agreement. Doable reserves the right to modify, limit, or discontinue the Free Tier at any time upon thirty (30) days' notice posted to doable.net or sent to your registered email address.</p>
            <h3>5.7 Price Changes for Existing Subscriptions</h3>
            <p>Doable will provide existing subscribers with at least thirty (30) days' advance written notice of any increase in the fees applicable to their current Subscription Plan. Such notice will be sent to the email address associated with User's Account. User's continued use of the Services following the effective date of any fee increase constitutes User's acceptance of the revised pricing. If User does not agree to any price increase, User must cancel its Subscription Plan in accordance with Section 6 before the price increase takes effect.</p>
            <h3>5.8 Refund Upon Termination for Convenience</h3>
            <p>If Doable terminates this Agreement for convenience pursuant to Section 20.2, Doable will refund to User any prepaid and unused subscription fees corresponding to the remainder of User's then-current prepaid Subscription Period on a pro-rata, daily basis.</p>
        </section>

        <!-- SECTION 6 -->
        <section class="terms-section" id="s6">
            <h2><span class="sec-num">6</span> Auto-Renewal, Cancellation, and Free Trial Policy</h2>
            <div class="terms-notice renewal">
                <strong>Auto-Renewal Disclosure — California and Multi-State Notice</strong>
                YOUR PAID SUBSCRIPTION PLAN WILL AUTOMATICALLY RENEW AT THE END OF EACH SUBSCRIPTION PERIOD (MONTHLY OR ANNUAL, AS APPLICABLE) FOR A SUCCESSIVE PERIOD OF THE SAME DURATION, AT THE THEN-CURRENT RATE FOR YOUR SELECTED SUBSCRIPTION PLAN, UNLESS YOU CANCEL YOUR SUBSCRIPTION AT LEAST SEVEN (7) CALENDAR DAYS BEFORE THE SCHEDULED RENEWAL DATE.<br><br>
                TO CANCEL YOUR SUBSCRIPTION AND PREVENT AUTO-RENEWAL: LOG INTO YOUR ACCOUNT AT DOABLE.NET, NAVIGATE TO ACCOUNT SETTINGS, SELECT "SUBSCRIPTION," AND CLICK "CANCEL SUBSCRIPTION." ALTERNATIVELY, YOU MAY EMAIL Info@doable.net (Subject: Account Cancellation) WITH YOUR ACCOUNT NAME AND EMAIL ADDRESS AND A CLEAR STATEMENT THAT YOU WISH TO CANCEL YOUR SUBSCRIPTION. CANCELLATION REQUESTS SUBMITTED BY EMAIL WILL BE CONFIRMED WITHIN TWO (2) BUSINESS DAYS.<br><br>
                YOUR CANCELLATION WILL TAKE EFFECT AT THE END OF YOUR THEN-CURRENT PAID SUBSCRIPTION PERIOD AND YOU WILL RETAIN ACCESS TO THE PAID FEATURES OF YOUR SUBSCRIPTION THROUGH THAT DATE. NO PARTIAL REFUNDS WILL BE ISSUED FOR THE REMAINING DAYS OF YOUR CURRENT SUBSCRIPTION PERIOD UNLESS DOABLE HAS TERMINATED YOUR ACCOUNT FOR CONVENIENCE PURSUANT TO SECTION 20.2.
            </div>
            <h3>6.1 Renewal Notice</h3>
            <p>Doable will send you a renewal reminder notice to your registered email address at least seven (7) calendar days before the scheduled auto-renewal date for annual Subscription Plans and at least three (3) calendar days before the scheduled auto-renewal date for monthly Subscription Plans, reminding you of the upcoming charge and providing cancellation instructions.</p>
            <h3>6.2 Cancellation Confirmation</h3>
            <p>Upon successful cancellation, Doable will send a cancellation confirmation to your registered email address. If you cancel and do not receive a confirmation email within two (2) business days, please contact Doable at Info@doable.net (Subject: Account Cancellation — Confirmation Needed) to verify cancellation.</p>
            <h3>6.3 Free Trial Policy</h3>
            <p>Doable may offer free trial periods for paid Subscription Plans (each, a "Free Trial"). During any Free Trial: (a) you will not be charged until the Free Trial ends; (b) you may use the Services subject to any limitations associated with the Free Trial; and (c) Doable will send a reminder notice to your registered email address three (3) calendar days before the Free Trial end date (the "Trial End Date") informing you of the upcoming charge and providing cancellation instructions. ON THE TRIAL END DATE, YOUR SELECTED SUBSCRIPTION PLAN WILL AUTOMATICALLY ACTIVATE AND YOUR DESIGNATED PAYMENT METHOD WILL BE CHARGED AT THE APPLICABLE SUBSCRIPTION RATE UNLESS YOU CANCEL YOUR SUBSCRIPTION BEFORE 11:59 P.M. MOUNTAIN TIME ON THE DAY BEFORE THE TRIAL END DATE. To cancel during a Free Trial, follow the cancellation instructions set forth at the beginning of this Section 6.</p>
            <h3>6.4 Free Trial Refund Policy</h3>
            <p>If you cancel your subscription within seventy-two (72) hours of being charged at the conclusion of a Free Trial period, and this is the first time you have enrolled in a Free Trial for that Subscription Plan, Doable will refund the charge in full. To request a Free Trial refund, email Info@doable.net (Subject: Free Trial Refund Request) within seventy-two (72) hours of the charge, including your Account email address and a brief statement that you are requesting a Free Trial refund. This refund policy does not apply to any charges after the initial Free Trial conversion.</p>
            <h3>6.5 California Auto-Renewal Law Compliance</h3>
            <p>For California residents: your subscription is subject to California's Automatic Renewal Law (Business and Professions Code §§ 17600-17606). By creating an Account and selecting a paid Subscription Plan: (a) you acknowledge that you have received and reviewed the auto-renewal terms as set forth in this Section 6; (b) you acknowledge that the auto-renewal terms have been presented in a clear and conspicuous manner; (c) your affirmative act of Account creation and Subscription Plan selection constitutes your express consent to auto-renewal on the disclosed terms; and (d) you understand that you may cancel auto-renewal at any time as described in Section 6. California residents have the right to receive a full refund if Doable fails to present the auto-renewal terms in a clear and conspicuous manner prior to purchase.</p>
            <h3>6.6 Canadian Consumer Protection Compliance</h3>
            <p>For Canadian Users: auto-renewal terms comply with applicable provincial consumer protection legislation. Ontario residents should consult the Consumer Protection Act, 2002, S.O. 2002, c. 30, Sched. A. Quebec residents should consult the Consumer Protection Act, CQLR c P-40.1. British Columbia residents should consult the Business Practices and Consumer Protection Act, SBC 2004, c 2. If applicable provincial law imposes additional rights with respect to cancellation, rescission, or refund of automatic renewal subscriptions, those rights apply and are not modified by this Agreement.</p>
        </section>

        <!-- SECTION 7 -->
        <section class="terms-section" id="s7">
            <h2><span class="sec-num">7</span> Beta Features and Experimental Functionality</h2>
            <h3>7.1 Beta Features</h3>
            <p>From time to time, Doable may make certain features, functions, integrations, or capabilities available in a pre-release or "beta" capacity ("Beta Features"). Beta Features may be identified by labels such as "Beta," "Preview," "Experimental," "Early Access," or similar terminology. By accessing or using any Beta Feature, you acknowledge and agree that:</p>
            <ol class="lettered">
                <li>Beta Features are provided on an "AS IS" and "AS AVAILABLE" basis, without warranty of any kind, and are excluded from all service commitments described in this Agreement, including the availability and support commitments in Section 14;</li>
                <li>Beta Features may contain errors, defects, or bugs and may be substantially modified, limited, suspended, or discontinued at any time, including without prior notice;</li>
                <li>Beta Features should not be used in production environments or for mission-critical operations;</li>
                <li>Doable shall have no liability to you for any loss, damage, corruption, or unauthorized disclosure of User Data resulting from your use of Beta Features; and</li>
                <li>any Feedback you provide regarding Beta Features is especially valuable to Doable and is subject to the Feedback assignment set forth in Section 12.3 of this Agreement.</li>
            </ol>
            <h3>7.2 No Commitment</h3>
            <p>Doable's provision of Beta Features does not represent a commitment to make any particular feature or functionality generally available. Doable may discontinue any Beta Feature at any time in its sole and absolute discretion.</p>
        </section>

        <!-- SECTION 8 -->
        <section class="terms-section" id="s8">
            <h2><span class="sec-num">8</span> Communications, Notifications, and Consent</h2>
            <h3>8.1 Transactional Communications</h3>
            <p>By creating an Account, you consent to receive transactional and service-related electronic communications from Doable, including but not limited to: account registration and verification emails, appointment booking confirmations and reminders, billing notices and receipts, auto-renewal notifications, service alerts and outage notices, security notifications, and in-application notifications. These transactional communications are integral to the delivery of the Services. You may not opt out of transactional communications while maintaining an active Account without closing your Account.</p>
            <h3>8.2 Marketing and Promotional Communications</h3>
            <p>Doable may send you marketing, promotional, and informational communications about Doable's products, services, features, and events. Doable will send you such communications only with your prior consent, which you may provide during Account registration or through your Account settings. You may opt out of marketing communications at any time by: (a) clicking the "unsubscribe" or "opt-out" link included in any marketing email; (b) adjusting your notification preferences in your Account settings; or (c) emailing Info@doable.net (Subject: Unsubscribe — Marketing Emails) with your Account email address and a request to unsubscribe. Doable will honor opt-out requests within ten (10) business days. Opting out of marketing communications will not affect your receipt of transactional communications.</p>
            <h3>8.3 SMS Text Message Notifications</h3>
            <p>By providing your mobile telephone number in your Account settings or during the scheduling process and consenting to receive SMS notifications, you agree that Doable may send appointment-related text messages to your mobile phone number, including booking confirmations, reminders, cancellation notices, and rescheduling alerts. Message and data rates may apply based on your mobile carrier's plan. The frequency of SMS messages will depend on your use of the Services. You may opt out of SMS notifications at any time by: (a) replying STOP to any text message sent by Doable (you will receive a confirmation text); or (b) updating your notification preferences in your Account settings. For assistance with SMS opt-out, email Info@doable.net (Subject: SMS Opt-Out Request).</p>
            <h3>8.4 CASL Compliance — Canadian Users</h3>
            <p>The following provisions apply specifically to Canadian Users and govern Doable's compliance with Canada's Anti-Spam Legislation (CASL, S.C. 2010, c. 23):</p>
            <ol class="lettered">
                <li><strong>Commercial Electronic Messages.</strong> Doable will send Commercial Electronic Messages (as defined under CASL) to Canadian Users only on the basis of express or implied consent as defined under CASL. By creating an Account, Canadian Users provide implied consent to receive Commercial Electronic Messages related to the Services for a period of two (2) years following your last transaction with Doable, unless express consent is obtained beforehand.</li>
                <li><strong>Message Requirements.</strong> Each Commercial Electronic Message sent by Doable to a Canadian User will: (i) clearly identify Doable as the sender; (ii) provide Doable's mailing address (1712 Pioneer Ave., Suite 783, Cheyenne, Wyoming 82001) and contact email (Info@doable.net); and (iii) include a clear, functional, and easily accessible unsubscribe mechanism that allows the recipient to unsubscribe from future Commercial Electronic Messages. Complaints regarding unsolicited messages may be sent to Info@doable.net (Subject: CASL Complaint or Spam Report).</li>
                <li><strong>Unsubscribe Processing.</strong> Doable will process all unsubscribe requests from Canadian Users within ten (10) business days of receipt at no cost to the subscriber.</li>
                <li><strong>Consent Withdrawal.</strong> Canadian Users may withdraw consent to receive Commercial Electronic Messages at any time without penalty as described in Section 8.2. Withdrawal of consent does not affect the lawfulness of Commercial Electronic Messages sent before withdrawal.</li>
                <li><strong>Implied Consent Expiry.</strong> Implied consent to receive Commercial Electronic Messages expires two (2) years following the date of the last commercial transaction between the Canadian User and Doable, unless express consent is provided before that date.</li>
            </ol>
            <h3>8.5 Electronic Communications as Legal Writing</h3>
            <p>You agree that all electronic communications from Doable — including emails, in-application notices, and website postings — satisfy any legal requirement that such communications be "in writing." You agree to keep your Account contact information current and to monitor your email and application notification settings. Doable is not responsible for any consequences arising from your failure to maintain current contact information or to monitor your communications.</p>
        </section>

        <!-- SECTION 9 -->
        <section class="terms-section" id="s9">
            <h2><span class="sec-num">9</span> User Data and Content</h2>
            <h3>9.1 Sole Responsibility for User Data</h3>
            <p>You are solely and exclusively responsible for all User Data that you or your Authorized Users upload, post, transmit, store, or otherwise make available through the Services. You represent and warrant that:</p>
            <ol class="lettered">
                <li>you own all User Data or have obtained all rights, licenses, permissions, and consents necessary to grant Doable the license set forth in Section 9.2 and to permit Doable to host, store, process, and display such User Data in connection with providing the Services;</li>
                <li>your User Data does not and will not infringe, misappropriate, or violate any Intellectual Property Rights, privacy rights, publicity rights, moral rights, or any other rights of any third party in any jurisdiction;</li>
                <li>your User Data does not and will not violate any applicable law, regulation, or judicial or governmental order;</li>
                <li>your User Data is not false, defamatory, misleading, or deceptive; and</li>
                <li>your User Data does not contain any material that is unlawful, harmful, threatening, harassing, abusive, tortious, defamatory, obscene, or otherwise objectionable.</li>
            </ol>
            <h3>9.2 License to User Data</h3>
            <p>You grant Doable a limited, non-exclusive, worldwide, royalty-free, sublicensable (solely to Doable's subprocessors and service providers) license to host, store, process, reproduce, transmit, display, and otherwise use your User Data solely and exclusively: (a) to provide the Services to you and your Authorized Users in accordance with this Agreement; (b) to improve, maintain, secure, and support the Services; and (c) as otherwise required by applicable law. This license is automatically revoked upon termination of your Account, subject to Doable's data retention obligations under Section 10.5.</p>
            <h3>9.3 No Sensitive Personal Information</h3>
            <p>You agree not to submit, upload, or transmit through the Services any data that constitutes "sensitive personal information," "special categories of data," or similarly designated high-risk categories of personal information under applicable law, including but not limited to: Social Security number or equivalent government identification numbers, driver's license or passport numbers, financial account numbers or payment card information (beyond what is provided to Doable's payment processor for subscription billing), health or medical information, biometric data, precise geolocation data, information about racial or ethnic origin, political opinions, religious beliefs, or sexual orientation. Doable's Services are not designed, intended, or certified to process sensitive personal information, and Doable disclaims all liability for any loss, harm, or regulatory consequence arising from User's submission of such information.</p>
            <h3>9.4 Doable's Limited Rights Over User Data</h3>
            <p>Doable will not sell, rent, or lease your User Data to third parties. Doable will not use your User Data for advertising purposes or to build advertising profiles. Doable may access, review, or disclose your User Data: (a) as required by applicable law, court order, or lawful governmental request; (b) to enforce this Agreement; (c) to investigate suspected violations of this Agreement or applicable law; (d) to protect the rights, property, or safety of Doable, its users, or the public; or (e) with your explicit prior consent.</p>
            <h3>9.5 Doable's Disclaimer Regarding User Data Quality</h3>
            <p>Doable does not control User Data and, as such, does not guarantee or warrant the accuracy, integrity, completeness, or quality of any User Data posted through the Services by any User. Doable is not responsible or liable for any error, omission, loss, or damage of any kind arising from or related to any User Data.</p>
            <h3>9.6 Data Security Measures</h3>
            <p>Doable employs commercially reasonable administrative, technical, and physical security measures designed to protect User Data from unauthorized access, loss, disclosure, alteration, and destruction. These measures include, without limitation, data encryption in transit and at rest, access controls, and regular security assessments. However, no method of electronic transmission or electronic storage is completely secure. You acknowledge that Doable cannot guarantee the absolute security of User Data, and Doable does not warrant that User Data will be free from unauthorized access, loss, alteration, or destruction. In the event of a data security breach that affects your User Data, Doable will notify you as required by applicable law.</p>
        </section>

        <!-- SECTION 10 -->
        <section class="terms-section" id="s10">
            <h2><span class="sec-num">10</span> Privacy and Data Protection</h2>
            <h3>10.1 Privacy Policy</h3>
            <p>Doable's collection, use, storage, retention, disclosure, and transfer of Personal Information is governed by Doable's Privacy Policy, available at https://doable.net/privacy-policy, which is incorporated into this Agreement by reference. By using the Services, you consent to Doable's data practices as described in the Privacy Policy. In the event of any conflict between the Privacy Policy and this Agreement with respect to the handling of Personal Information, the Privacy Policy shall control.</p>
            <h3>10.2 United States Privacy Rights</h3>
            <p>Doable acknowledges and respects the privacy rights of Users located in US states with applicable consumer privacy laws. As of the effective date of this Agreement, the following US state privacy laws apply to Doable's operations: the California Privacy Rights Act (CPRA, as amended); the Virginia Consumer Data Protection Act; the Colorado Privacy Act; the Connecticut Data Privacy Act; the Texas Data Privacy and Security Act; the Oregon Consumer Privacy Act; the Montana Consumer Data Privacy Act; and similar laws enacted by other states. Users located in these jurisdictions have certain rights with respect to their Personal Information, including the right to: (a) know whether Doable processes their Personal Information and to access such information; (b) correct inaccurate Personal Information; (c) delete Personal Information, subject to applicable exceptions; (d) receive a copy of Personal Information in a portable format; (e) opt out of the sale or sharing of Personal Information for cross-context behavioral advertising; and (f) limit the use of sensitive Personal Information where applicable. To exercise any of these rights, contact Doable at Info@doable.net (Subject: Privacy Rights Request — followed by the name of your state of residence, for example, "Privacy Rights Request — California") or consult the Privacy Policy for detailed instructions. Doable will not discriminate against any User for exercising their privacy rights.</p>
            <h3>10.3 Canadian Privacy Rights — Federal Law (PIPEDA)</h3>
            <p>Doable's collection, use, and disclosure of Personal Information of Canadian Users is governed by Canada's Personal Information Protection and Electronic Documents Act (PIPEDA, S.C. 2000, c. 5) and, where superseded, applicable provincial privacy legislation. Under PIPEDA, Canadian Users have the right to: (a) access their Personal Information held by Doable; (b) request correction of inaccurate or incomplete Personal Information; (c) withdraw consent to certain uses of their Personal Information, subject to legal and contractual restrictions; and (d) file a complaint with the Office of the Privacy Commissioner of Canada regarding Doable's handling of their Personal Information. To exercise PIPEDA rights, contact Doable's Privacy Officer at Info@doable.net (Subject: PIPEDA Privacy Request).</p>
            <h3>10.4 Canadian Privacy Rights — Quebec (Law 25)</h3>
            <p>For Users located in the Province of Quebec, Doable's handling of Personal Information is governed by Quebec's Act respecting the protection of personal information in the private sector (CQLR c P-39.1, as amended by An Act to Modernize Legislative Provisions as regards the Protection of Personal Information, 2021, c. 25 — commonly known as "Law 25," fully in force as of September 22, 2023). In addition to PIPEDA rights, Quebec Users have the right to: (a) be informed of the purposes for which their Personal Information is collected; (b) access and correct their Personal Information; (c) request the cessation of dissemination of their Personal Information; (d) request the re-indexing of Personal Information; and (e) data portability in a structured, commonly-used technological format. Quebec Users may also file a complaint with the Commission d'accès à l'information (CAI) regarding Doable's compliance with Law 25. To exercise Quebec privacy rights, contact Info@doable.net (Subject: Quebec Law 25 Privacy Request).</p>
            <h3>10.5 Data Retention</h3>
            <p>Doable retains User Data for the duration of the Account and for up to ninety (90) days following Account termination or closure, after which Doable will delete or irreversibly anonymize User Data, except as required by applicable law, regulatory obligation, legal hold, or legitimate business purposes (such as fraud prevention, dispute resolution, and enforcement of this Agreement). During the ninety (90)-day post-termination period, User may export their User Data through Account settings or by contacting Info@doable.net (Subject: Data Export Request). Doable's data retention practices are described in more detail in the Privacy Policy.</p>
            <h3>10.6 Enterprise Data Processing Addendum</h3>
            <p>When an Enterprise Customer uses Doable to process Personal Information of its own clients, employees, or other data subjects, Doable acts as a data processor on behalf of the Enterprise Customer, who acts as the data controller. Enterprise Customers who are subject to applicable privacy laws requiring a data processing agreement (including the CPRA, PIPEDA, Law 25, or similar legislation) may request Doable's standard Data Processing Addendum by contacting Info@doable.net (Subject: Data Processing Addendum Request). Execution of a DPA is required for Enterprise Customers whose use of the Services involves the processing of Personal Information of third parties.</p>
        </section>

        <!-- SECTION 11 -->
        <section class="terms-section" id="s11">
            <h2><span class="sec-num">11</span> Acceptable Use Policy</h2>
            <h3>11.1 General Obligation</h3>
            <p>You agree to use the Services only for lawful purposes, only in accordance with this Agreement, and only in a manner consistent with all applicable federal, state, provincial, and local laws and regulations. You are responsible for all activities conducted under your Account, including the activities of your Authorized Users.</p>
            <h3>11.2 Prohibited Uses and Conduct</h3>
            <p>You agree that you will not, and will ensure that your Authorized Users do not, use the Services to:</p>
            <ol class="lettered">
                <li>upload, post, transmit, or otherwise make available any User Data or content that: (i) is unlawful, harmful, threatening, abusive, harassing, defamatory, libelous, obscene, pornographic, invasive of another's privacy, or otherwise objectionable; (ii) promotes, glorifies, or encourages violence, discrimination, or illegal activities; (iii) constitutes hate speech directed at any person or group based on race, color, national origin, ethnicity, religion, gender, gender identity or expression, sexual orientation, disability, age, or other protected characteristic; or (iv) User does not have the right to post under any applicable law or under any contractual, fiduciary, or confidentiality obligation;</li>
                <li>impersonate any person or entity, falsely claim an affiliation with any person or entity, or misrepresent the origin, identity, or authenticity of any content;</li>
                <li>infringe, misappropriate, or violate any Intellectual Property Right of any person or entity in any jurisdiction, including by posting copyrighted material without authorization, using another party's trademarks without permission, or disclosing another party's trade secrets;</li>
                <li>transmit, post, or facilitate the distribution of any unsolicited or unauthorized commercial communications, including bulk email ("spam"), chain letters, pyramid schemes, phishing messages, or similar unsolicited communications, in violation of the CAN-SPAM Act (15 U.S.C. § 7701 et seq.), Canada's Anti-Spam Legislation (CASL), or any other applicable anti-spam or consumer protection law;</li>
                <li>harm, exploit, solicit, or endanger the safety or wellbeing of any minor under the age of eighteen (18) in any manner;</li>
                <li>upload, transmit, or distribute any malicious code, including software viruses, worms, trojan horses, ransomware, spyware, adware, rootkits, or any other harmful or disruptive computer code, file, script, or program;</li>
                <li>interfere with, disrupt, impair, or attempt to gain unauthorized access to the Services, Doable's servers or networks, any user's Account, or any third-party server, network, system, or service connected to or accessible through the Services;</li>
                <li>use the Services to facilitate any fraudulent activity, including identity theft, credit card fraud, phishing, or account takeover attacks;</li>
                <li>collect, harvest, or aggregate any Personal Information or non-personal information about other users of the Services without their express consent, including email addresses, phone numbers, or user identifiers;</li>
                <li>violate any applicable local, state, provincial, federal, or international law, regulation, rule, judicial order, or governmental directive; or</li>
                <li>use Doable's name, trademarks, service marks, logos, or other brand elements to endorse or promote any third-party product, service, or business without Doable's prior written consent.</li>
            </ol>
            <h3>11.3 Enforcement Rights</h3>
            <p>Doable reserves the right, but has no obligation, to: (a) monitor the Services for violations of this Acceptable Use Policy; (b) investigate any reported or suspected violation of this Agreement or applicable law; (c) remove or disable any User Data or content that violates this Agreement or that Doable determines, in its sole discretion, is otherwise harmful or objectionable; (d) suspend or terminate any Account without prior notice in response to a violation; and (e) report suspected illegal activity to law enforcement authorities, regulators, or other governmental authorities and disclose information about Users as permitted or required by applicable law. Doable's exercise of these rights does not create an obligation to monitor or take action in any particular case, and Doable shall not be liable to you or any third party for any action or failure to act under this Section 11.3.</p>
        </section>

        <!-- SECTION 12 -->
        <section class="terms-section" id="s12">
            <h2><span class="sec-num">12</span> Intellectual Property Rights</h2>
            <h3>12.1 Ownership of Services and Materials</h3>
            <p>The Services, including all software, code, algorithms, databases, interfaces, designs, layouts, graphics, text, audio, video, and other elements comprising the Services and Materials (collectively, "Doable Content"), are owned exclusively by Doable or its licensors and are protected by applicable United States copyright law (17 U.S.C. § 101 et seq.), Canadian copyright law (Copyright Act, R.S.C. 1985, c. C-42), trademark law, patent law, trade secret law, and other applicable Intellectual Property Rights laws of the United States, Canada, and international jurisdictions. No right, title, or interest in or to the Doable Content or any Intellectual Property Rights therein is transferred to User by this Agreement or by User's access to the Services.</p>
            <h3>12.2 Trademarks and Brand</h3>
            <p>"Doable," "Doable.net," and all associated logos, icons, product names, taglines, and other brand elements (collectively, "Doable Marks") are trademarks or service marks of Doable LLC registered or used in the United States and may be registered or used in Canada and other jurisdictions. User may not use any Doable Marks — including in advertising, press releases, promotional materials, or on User's website — without Doable's prior written consent. User may state, in a factually accurate manner, that User uses Doable's scheduling Services, provided that such use does not imply endorsement, partnership, or affiliation with Doable, does not disparage Doable or its Services, and complies with any brand guidelines published by Doable. All other trademarks, service marks, and logos appearing on the Services are the property of their respective owners.</p>
            <h3>12.3 Feedback Assignment</h3>
            <p>If you provide Doable with any Feedback — including suggestions, ideas, feature requests, bug reports, or comments about the Services — you hereby irrevocably assign to Doable all right, title, and interest in and to such Feedback, including all Intellectual Property Rights therein, effective immediately upon Doable's receipt of such Feedback. This assignment is made without compensation, restriction, or obligation. Doable shall have the unrestricted right to use, reproduce, modify, adapt, publish, distribute, sublicense, and create derivative works from any Feedback for any purpose, including incorporating Feedback into the Services, without attribution, compensation, or further obligation to you.</p>
            <h3>12.4 DMCA / Copyright Infringement Reporting</h3>
            <p>If you believe in good faith that content available through the Services infringes your copyright, you may submit a complaint notice under the Digital Millennium Copyright Act (17 U.S.C. § 512) to Doable's designated Copyright Agent as follows:</p>
            <p><strong>By Email (preferred):</strong> Info@doable.net (Subject: DMCA Copyright Infringement Notice)<br>
                <strong>By Mail:</strong> Doable LLC, Attn: Copyright Agent, 1712 Pioneer Ave., Suite 783, Cheyenne, Wyoming 82001
            </p>
            <p>Your DMCA notice must include:</p>
            <ol class="lettered">
                <li>a physical or electronic signature of the copyright owner or an authorized agent;</li>
                <li>identification of the copyrighted work claimed to have been infringed;</li>
                <li>identification of the infringing material and information sufficient to locate it;</li>
                <li>your contact information (address, telephone number, and email);</li>
                <li>a statement that you have a good-faith belief that use of the material is not authorized by the copyright owner, its agent, or the law; and</li>
                <li>a statement, under penalty of perjury, that the information in your notice is accurate and that you are the copyright owner or authorized to act on their behalf.</li>
            </ol>
            <p>Doable will terminate, in appropriate circumstances, the Accounts of Users who are repeat infringers.</p>
            <h3>12.5 Canadian Copyright Complaints</h3>
            <p>For copyright complaints related to Canadian copyrights under the Copyright Act (R.S.C. 1985, c. C-42), please contact Doable at Info@doable.net (Subject: Canadian Copyright Complaint) with the same information required for a DMCA notice. Doable will investigate all copyright complaints and will respond as required by applicable law.</p>
            <h3>12.6 Counter-Notices</h3>
            <p>If content you posted has been removed in response to a DMCA notice and you believe the removal was in error, you may submit a DMCA counter-notice to Info@doable.net (Subject: DMCA Counter-Notice). Doable will process counter-notices in accordance with applicable law.</p>
        </section>

        <!-- SECTION 13 -->
        <section class="terms-section" id="s13">
            <h2><span class="sec-num">13</span> Third-Party Services, Integrations, and Links</h2>
            <h3>13.1 Third-Party Links</h3>
            <p>The Services may contain links or references to third-party websites, applications, products, or services that are not owned, operated, or controlled by Doable ("Third-Party Services"). Doable provides these links for convenience only and does not endorse, approve, control, or accept any responsibility for the content, privacy practices, security, availability, or terms of any Third-Party Service. Your access to and use of any Third-Party Service is at your own risk and subject to that third party's own terms of service and privacy policy. Doable is not responsible for any loss, damage, or harm arising from your use of any Third-Party Service.</p>
            <h3>13.2 Third-Party Integrations</h3>
            <p>The Services may offer optional integrations with Third-Party Services, such as Google Calendar, Microsoft Outlook, Zoom, Microsoft Teams, and other productivity tools (each, a "Third-Party Integration"). When you enable a Third-Party Integration, you authorize Doable to access, retrieve, and transmit data between the Services and the applicable Third-Party Service as necessary to enable the integration. Your use of any Third-Party Integration is governed by: (a) this Agreement; and (b) the terms of service and privacy policy of the applicable third party. Doable is not responsible for the availability, functionality, accuracy, security, or privacy practices of any Third-Party Integration. You should review the applicable third party's terms before enabling any integration.</p>
            <h3>13.3 Apple App Store Terms</h3>
            <p>To the extent User accesses or downloads the Doable mobile application through Apple Inc.'s App Store, the following additional terms apply:</p>
            <ol class="lettered">
                <li>This Agreement is entered into between User and Doable only, and not with Apple Inc. ("Apple");</li>
                <li>Apple is not responsible for the Doable application or its content;</li>
                <li>Apple has no obligation to furnish any maintenance or support services with respect to the Doable application;</li>
                <li>in the event of any failure of the Doable application to conform to any applicable warranty, User may notify Apple, and Apple will refund the purchase price (if any) for the application; Apple has no other warranty obligation whatsoever with respect to the Doable application;</li>
                <li>Apple is not responsible for addressing any claims by User or any third party relating to the Doable application; and</li>
                <li>Apple is a third-party beneficiary of this Agreement and has the right to enforce it as a third-party beneficiary.</li>
            </ol>
            <p>The Apple Licensed Application End User License Agreement (available at https://www.apple.com/legal/internet-services/itunes/dev/stdeula/) is incorporated by reference into this Agreement. In the event of a conflict between the Apple LAEULA and this Agreement, this Agreement shall control.</p>
            <h3>13.4 Google Play Terms</h3>
            <p>To the extent User accesses or downloads the Doable mobile application through Google LLC's Google Play Store, Google's Terms of Service (available at https://play.google.com/about/play-terms/) are incorporated by reference into this Agreement to the extent required by Google. In the event of a conflict between Google's Terms of Service and this Agreement, this Agreement shall control.</p>
        </section>

        <!-- SECTION 14 -->
        <section class="terms-section" id="s14">
            <h2><span class="sec-num">14</span> Service Availability and Support</h2>
            <h3>14.1 Service Availability</h3>
            <p>Doable will use commercially reasonable efforts to make the Services available twenty-four (24) hours a day, seven (7) days a week, except for scheduled maintenance windows and circumstances beyond Doable's reasonable control. Doable does not guarantee uninterrupted access to the Services and does not warrant that the Services will be available at all times or in all geographic areas.</p>
            <h3>14.2 Scheduled Maintenance</h3>
            <p>Doable may take the Services offline for scheduled maintenance. Where reasonably practicable, Doable will provide advance notice of scheduled maintenance via posting to the Doable status page or by email to registered users. Doable will endeavor to schedule maintenance during off-peak hours.</p>
            <h3>14.3 Customer Support</h3>
            <p>Doable provides customer support for the Services via email at Info@doable.net (Subject: Customer Support). Doable will use commercially reasonable efforts to respond to support inquiries within two (2) business days. Support is available Monday through Friday, during Doable's standard business hours (Mountain Time). Response times and support scope may vary based on User's Subscription Plan.</p>
            <h3>14.4 System Requirements</h3>
            <p>You are solely responsible for obtaining and maintaining all hardware, software, internet access, and other infrastructure necessary to access and use the Services. Doable is not responsible for any inability to access the Services resulting from deficiencies in your equipment, software, or internet connection.</p>
        </section>

        <!-- SECTION 15 -->
        <section class="terms-section" id="s15">
            <h2><span class="sec-num">15</span> Disclaimer of Warranties</h2>
            <p>TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, AND TO THE FULL EXTENT THAT SUCH A DISCLAIMER IS ENFORCEABLE UNDER WYOMING LAW AND THE LAWS OF ALL OTHER APPLICABLE JURISDICTIONS:</p>
            <h3>15.1 "As Is" and "As Available"</h3>
            <p>THE SERVICES AND MATERIALS ARE PROVIDED ON AN "AS IS" AND "AS AVAILABLE" BASIS, WITH ALL FAULTS AND WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED. DOABLE LLC, ITS MEMBERS, MANAGERS, OFFICERS, EMPLOYEES, AGENTS, LICENSORS, AND THIRD-PARTY SERVICE PROVIDERS (COLLECTIVELY, "DOABLE PARTIES") HEREBY EXPRESSLY DISCLAIM ALL WARRANTIES, WHETHER EXPRESS, IMPLIED, STATUTORY, OR ARISING FROM COURSE OF DEALING, COURSE OF PERFORMANCE, OR TRADE USAGE, INCLUDING WITHOUT LIMITATION:</p>
            <ol class="lettered">
                <li>THE IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, TITLE, AND NON-INFRINGEMENT OF THIRD-PARTY RIGHTS;</li>
                <li>ANY WARRANTY THAT THE SERVICES WILL BE UNINTERRUPTED, TIMELY, SECURE, ERROR-FREE, OR FREE FROM VIRUSES, MALWARE, OR OTHER HARMFUL COMPONENTS;</li>
                <li>ANY WARRANTY THAT DEFECTS IN THE SERVICES WILL BE CORRECTED OR THAT THE SERVICES WILL MEET USER'S REQUIREMENTS OR EXPECTATIONS;</li>
                <li>ANY WARRANTY THAT THE RESULTS OBTAINED FROM USE OF THE SERVICES WILL BE ACCURATE, RELIABLE, COMPLETE, OR SUFFICIENT FOR ANY PARTICULAR PURPOSE; AND</li>
                <li>ANY WARRANTY AS TO THE ACCURACY, COMPLETENESS, OR RELIABILITY OF ANY CONTENT, DATA, OR INFORMATION PROVIDED THROUGH OR ACCESSIBLE VIA THE SERVICES, INCLUDING THIRD-PARTY CONTENT AND USER DATA.</li>
            </ol>
            <h3>15.2 No Professional Advice</h3>
            <p>THE SERVICES DO NOT CONSTITUTE AND ARE NOT A SUBSTITUTE FOR LEGAL, FINANCIAL, MEDICAL, PROFESSIONAL, OR OTHER ADVICE OR SERVICES. ANY CONTENT OR INFORMATION AVAILABLE THROUGH THE SERVICES IS FOR GENERAL INFORMATIONAL PURPOSES ONLY AND SHOULD NOT BE RELIED UPON FOR ANY SPECIFIC PURPOSE OR AS A SUBSTITUTE FOR PROFESSIONAL CONSULTATION. DOABLE DISCLAIMS ALL LIABILITY FOR ANY RELIANCE PLACED ON ANY SUCH CONTENT OR INFORMATION.</p>
            <h3>15.3 Beta Features</h3>
            <p>BETA FEATURES ARE EXPRESSLY EXCLUDED FROM ALL WARRANTIES. DOABLE MAKES NO REPRESENTATIONS OR WARRANTIES OF ANY KIND WITH RESPECT TO BETA FEATURES. YOUR USE OF ANY BETA FEATURE IS ENTIRELY AT YOUR OWN RISK.</p>
            <h3>15.4 Third-Party Content</h3>
            <p>DOABLE DISCLAIMS ALL WARRANTIES WITH RESPECT TO CONTENT, PRODUCTS, SERVICES, OR INFORMATION PROVIDED BY THIRD PARTIES, INCLUDING THIRD-PARTY INTEGRATIONS, AND ANY LINKED THIRD-PARTY WEBSITES. DOABLE IS NOT RESPONSIBLE FOR AND DOES NOT ENDORSE ANY THIRD-PARTY CONTENT.</p>
            <h3>15.5 Canadian Users — Consumer Protection</h3>
            <p>NOTHING IN THIS SECTION 15 IS INTENDED TO EXCLUDE ANY WARRANTY, REPRESENTATION, OR CONDITION THAT CANNOT LAWFULLY BE EXCLUDED UNDER APPLICABLE CANADIAN PROVINCIAL CONSUMER PROTECTION LEGISLATION. TO THE EXTENT THAT ANY SUCH MANDATORY WARRANTY, REPRESENTATION, OR CONDITION APPLIES, IT IS HEREBY INCORPORATED INTO THIS AGREEMENT, AND DOABLE'S LIABILITY FOR ANY BREACH THEREOF IS LIMITED TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW.</p>
        </section>

        <!-- SECTION 16 -->
        <section class="terms-section" id="s16">
            <h2><span class="sec-num">16</span> Limitation of Liability</h2>
            <p>TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW:</p>
            <h3>16.1 Exclusion of Consequential and Related Damages</h3>
            <p>IN NO EVENT SHALL ANY DOABLE PARTY BE LIABLE TO USER OR ANY THIRD PARTY FOR:</p>
            <ol class="lettered">
                <li>ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, PUNITIVE, OR EXEMPLARY DAMAGES OF ANY NATURE;</li>
                <li>DAMAGES FOR LOSS OF PROFITS, REVENUE, BUSINESS, DATA, GOODWILL, ANTICIPATED SAVINGS, BUSINESS OPPORTUNITY, OR REPUTATION;</li>
                <li>LOSS OR CORRUPTION OF USER DATA OR SYSTEM FAILURE;</li>
                <li>COST OF PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; OR</li>
                <li>ANY OTHER ECONOMIC OR COMMERCIAL LOSS — IN EACH CASE ARISING OUT OF OR RELATED TO THIS AGREEMENT, THE SERVICES, OR USER'S USE OF OR INABILITY TO USE THE SERVICES, HOWEVER CAUSED AND UNDER ANY THEORY OF LIABILITY (WHETHER IN CONTRACT, TORT INCLUDING NEGLIGENCE, STRICT LIABILITY, STATUTE, OR OTHERWISE), EVEN IF ANY DOABLE PARTY HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES AND EVEN IF A LIMITED REMEDY FAILS OF ITS ESSENTIAL PURPOSE.</li>
            </ol>
            <h3>16.2 Aggregate Liability Cap</h3>
            <p>TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, THE TOTAL CUMULATIVE LIABILITY OF ALL DOABLE PARTIES TO USER FOR ALL CLAIMS OF ANY KIND ARISING OUT OF OR RELATED TO THIS AGREEMENT OR THE SERVICES SHALL NOT EXCEED THE GREATER OF: (a) THE TOTAL SUBSCRIPTION FEES ACTUALLY PAID BY USER TO DOABLE IN THE TWELVE (12) CALENDAR MONTHS IMMEDIATELY PRECEDING THE DATE ON WHICH THE CLAIM OR CAUSE OF ACTION AROSE; OR (b) ONE HUNDRED UNITED STATES DOLLARS (USD $100) (OR, FOR CANADIAN USERS, ONE HUNDRED THIRTY-FIVE CANADIAN DOLLARS (CAD $135)). THIS CAP APPLIES IN THE AGGREGATE TO ALL CLAIMS, REGARDLESS OF THE FORM OF ACTION, WHETHER IN CONTRACT, TORT, STATUTE, EQUITY, OR OTHERWISE.</p>
            <h3>16.3 Independent Allocation</h3>
            <p>THE PARTIES ACKNOWLEDGE AND AGREE THAT THE LIMITATIONS OF LIABILITY AND DISCLAIMERS SET FORTH IN SECTIONS 15 AND 16 REFLECT A REASONABLE AND FAIR ALLOCATION OF RISK BETWEEN THE PARTIES AND ARE AN ESSENTIAL ELEMENT OF THE BASIS OF THE BARGAIN BETWEEN DOABLE AND USER. THE FEES CHARGED BY DOABLE REFLECT AND ARE SET IN RELIANCE UPON THESE LIMITATIONS OF LIABILITY. THESE LIMITATIONS SHALL APPLY NOTWITHSTANDING ANY FAILURE OF ESSENTIAL PURPOSE OF ANY LIMITED REMEDY.</p>
            <h3>16.4 Canadian User Consumer Rights</h3>
            <p>CERTAIN CANADIAN PROVINCIAL LEGISLATION (INCLUDING LEGISLATION IN QUEBEC) MAY NOT PERMIT THE EXCLUSION OR LIMITATION OF LIABILITY FOR PERSONAL INJURY OR FOR FRAUDULENT MISREPRESENTATION. TO THE EXTENT THAT SUCH MANDATORY PROVINCIAL LAWS APPLY, SOME OF THE LIMITATIONS IN THIS SECTION 16 MAY NOT APPLY TO YOU. THE LIMITATIONS OF LIABILITY IN THIS SECTION 16 DO NOT EXCLUDE OR LIMIT ANY LIABILITY THAT CANNOT LAWFULLY BE LIMITED OR EXCLUDED UNDER APPLICABLE CANADIAN PROVINCIAL LAW.</p>
        </section>

        <!-- SECTION 17 -->
        <section class="terms-section" id="s17">
            <h2><span class="sec-num">17</span> Indemnification</h2>
            <h3>17.1 User's Indemnification Obligation</h3>
            <p>User agrees to indemnify, defend (with counsel acceptable to Doable in its reasonable discretion), and hold harmless the Doable Indemnitees from and against any and all claims, demands, actions, proceedings, losses, liabilities, damages, judgments, penalties, fines, costs, and expenses (including reasonable attorneys' fees and court costs) ("Losses") arising out of or relating to:</p>
            <ol class="lettered">
                <li>any actual or alleged infringement, misappropriation, or violation of any Intellectual Property Right of any person or entity in any jurisdiction — including any U.S. or Canadian patent, copyright, trademark, trade secret, or other proprietary right — arising from User Data, User's trademarks or brand materials, or User's or Authorized Users' use of the Services;</li>
                <li>any breach by User or its Authorized Users of any representation, warranty, covenant, obligation, or other provision of this Agreement;</li>
                <li>User's or Authorized Users' violation of any applicable law or regulation, including any privacy law (including CPRA, PIPEDA, Law 25, and similar laws), anti-spam law (including CASL and the CAN-SPAM Act), consumer protection law, export control law, or sanctions regulation;</li>
                <li>User's or Authorized Users' negligence, gross negligence, willful misconduct, fraud, or intentional wrongful acts or omissions in connection with the Services;</li>
                <li>any claim by a third party arising from or related to User Data, User's collection, processing, or use of third-party Personal Information through the Services, or User's or Authorized Users' use of the Services to interact with third parties; or</li>
                <li>any claim alleging that Doable is liable to a third party as a result of User's or Authorized Users' use of the Services, except to the extent that such claim arises from Doable's own gross negligence or willful misconduct.</li>
            </ol>
            <h3>17.2 Indemnification Procedure</h3>
            <p>Doable will: (a) notify User in writing of any indemnifiable claim promptly after becoming aware of it (provided that any failure to notify shall not relieve User's indemnification obligation except to the extent User is actually and materially prejudiced by such failure); (b) grant User control over the defense and settlement of such claim (provided that User shall not settle any claim that imposes any obligation, restriction, or liability on any Doable Indemnitee without Doable's prior written consent, which shall not be unreasonably withheld); and (c) provide reasonable cooperation in the defense of such claim at User's reasonable expense. Doable reserves the right to participate in the defense of any indemnifiable claim with counsel of its own choosing, at its own expense.</p>
        </section>

        <!-- SECTION 18 -->
        <section class="terms-section" id="s18">
            <h2><span class="sec-num">18</span> Dispute Resolution and Mandatory Binding Arbitration</h2>
            <div class="terms-notice arbitration">
                <strong>Please Read This Section Carefully</strong>
                It contains provisions for mandatory binding arbitration and a class action waiver that substantially affect your legal rights.
            </div>
            <h3>18.1 Informal Dispute Resolution — Mandatory Pre-Condition</h3>
            <p>Before either party initiates any formal dispute proceeding, the parties agree to use commercially reasonable good-faith efforts to resolve any dispute, claim, or controversy arising out of or relating to this Agreement, the Services, or the relationship between the parties ("Dispute") through informal negotiation. The party initiating the Dispute must first send a written notice to the other party describing the nature of the Dispute and the relief requested (a "Dispute Notice"): (a) if User is initiating — by email to Info@doable.net (Subject: Dispute Notice); (b) if Doable is initiating — by email to the User's registered email address.</p>
            <h3>18.2 Mandatory Binding Arbitration</h3>
            <p>If a Dispute is not resolved during the Informal Resolution Period, either party may initiate binding arbitration. Both parties agree that any and all Disputes (except those described in Section 18.9) shall be resolved exclusively through final, binding, and confidential arbitration, rather than in any court of law. This agreement to arbitrate is governed by the Federal Arbitration Act (9 U.S.C. § 1 et seq.) and, to the extent applicable, the arbitration laws of the State of Wyoming.</p>
            <h3>18.3 Arbitration Rules and Administrator</h3>
            <ol class="lettered">
                <li><strong>US Disputes.</strong> Arbitration for US-based Disputes shall be administered by the American Arbitration Association ("AAA") in accordance with its: (i) Consumer Arbitration Rules (available at www.adr.org/consumer), for Disputes initiated by individual consumers; or (ii) Commercial Arbitration Rules (available at www.adr.org/commercial), for Disputes initiated by Enterprise Customers or involving amounts in dispute exceeding $75,000. In the event of any conflict between the AAA Rules and this Agreement, this Agreement shall control.</li>
                <li><strong>Canadian Disputes.</strong> Arbitration for Canadian-based Disputes shall be administered by the ADR Institute of Canada ("ADRIC") in accordance with its National Arbitration Rules (available at www.adric.ca), unless the parties agree in writing to ADR Institute of Ontario rules or another mutually acceptable arbitration institution. In the event of any conflict between ADRIC rules and this Agreement, this Agreement shall control.</li>
                <li><strong>Arbitrator Selection.</strong> The arbitration shall be conducted by a single neutral arbitrator who is a retired judge or practicing attorney with at least ten (10) years of experience in commercial or technology law, selected in accordance with the applicable arbitration rules.</li>
            </ol>
            <h3>18.4 Arbitration Seat, Location, and Procedure</h3>
            <ol class="lettered">
                <li>The seat (legal place) of arbitration for US Disputes is Cheyenne, Wyoming. The seat for Canadian Disputes is Toronto, Ontario.</li>
                <li>Unless the arbitrator determines that an in-person hearing is required, all proceedings, hearings, and submissions shall be conducted by telephone, video conference, or written submission at the option of the party initiating arbitration.</li>
                <li>If an in-person hearing is required for a US Dispute, it shall be held in Cheyenne, Wyoming, unless the parties agree in writing to an alternative location. If an in-person hearing is required for a Canadian Dispute, it shall be held in Toronto, Ontario.</li>
                <li>The arbitration shall be conducted in the English language.</li>
                <li>The arbitrator shall apply applicable substantive law (as specified in Section 19) and shall be empowered to award any remedy that would be available in a court of competent jurisdiction. The arbitrator shall have no authority to award punitive or exemplary damages except where expressly authorized by applicable statute, and any such award shall be subject to the aggregate liability cap in Section 16.2.</li>
                <li>The arbitrator's written award shall include sufficient findings of fact and conclusions of law to explain the decision. The award shall be final and binding on the parties and may be entered as a judgment in any court of competent jurisdiction.</li>
            </ol>
            <h3>18.5 Arbitration Fees and Costs</h3>
            <ol class="lettered">
                <li>AAA and ADRIC filing, case management, and arbitrator fees shall be allocated between the parties in accordance with the applicable arbitration rules, except as provided in Subsection (b).</li>
                <li>For consumer arbitration claims initiated by an individual User where the amount in dispute does not exceed $10,000 USD (or CAD equivalent), Doable will pay the AAA or ADRIC filing fee and all case management and arbitrator fees, provided that the arbitrator does not determine that the claim is frivolous.</li>
                <li>Each party shall bear its own attorneys' fees and costs in arbitration, unless the applicable arbitration rules or applicable statute provides for an award of fees, or unless the arbitrator finds that a claim or defense was filed without any reasonable basis.</li>
            </ol>
            <h3>18.6 Class Action Waiver — Critical Provision</h3>
            <p>TO THE FULLEST EXTENT PERMITTED BY APPLICABLE LAW, BOTH DOABLE AND USER IRREVOCABLY WAIVE THE RIGHT TO INITIATE, PARTICIPATE IN, OR RECOVER FROM ANY CLASS ACTION LAWSUIT, CLASS-WIDE ARBITRATION, COLLECTIVE ACTION, PRIVATE ATTORNEY GENERAL ACTION, OR ANY OTHER REPRESENTATIVE OR MULTI-PLAINTIFF PROCEEDING ARISING OUT OF OR RELATING TO THIS AGREEMENT OR THE SERVICES, WHETHER IN COURT OR IN ARBITRATION. ALL DISPUTES MUST BE BROUGHT BY THE PARTIES EXCLUSIVELY IN THEIR INDIVIDUAL CAPACITIES. THE ARBITRATOR SHALL HAVE NO AUTHORITY TO CONSOLIDATE TWO OR MORE INDIVIDUALS' CLAIMS INTO A SINGLE PROCEEDING, TO HEAR ANY DISPUTE AS A CLASS OR REPRESENTATIVE ACTION, OR TO AWARD RELIEF TO A GROUP OR CLASS OF PERSONS. IF A COURT OF COMPETENT JURISDICTION FINDS THAT THIS CLASS ACTION WAIVER IS UNENFORCEABLE WITH RESPECT TO A PARTICULAR CLAIM OR PROCEEDING, THEN THAT CLAIM OR PROCEEDING SHALL BE SEVERED FROM ARBITRATION AND MAY PROCEED IN A COURT OF COMPETENT JURISDICTION, BUT ONLY ON AN INDIVIDUAL (NON-CLASS) BASIS.</p>
            <h3>18.7 Jury Trial Waiver</h3>
            <p>TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, BOTH DOABLE AND USER IRREVOCABLY WAIVE ANY AND ALL RIGHTS TO A TRIAL BY JURY WITH RESPECT TO ANY DISPUTE ARISING OUT OF OR RELATING TO THIS AGREEMENT OR THE SERVICES.</p>
            <h3>18.8 Opt-Out Right</h3>
            <p>You have the right to opt out of the mandatory arbitration and class action waiver provisions of this Section 18 by sending a written opt-out notice to Doable within sixty (60) calendar days of the date you first accept these Terms. To exercise this opt-out right, you must send an email to Info@doable.net (Subject: Arbitration Opt-Out Notice) that includes: (a) your full legal name; (b) the email address associated with your Doable Account; (c) your Account username (if applicable); and (d) a clear statement that you wish to opt out of the arbitration and class action waiver provisions of the Doable Terms of Use. Opting out of arbitration will not affect any other term of this Agreement, will not affect Doable's provision of the Services to you, and will not result in any adverse action against your Account. If you do not opt out within the sixty (60)-day period, you will be bound by the arbitration and class action waiver provisions.</p>
            <h3>18.9 Exceptions</h3>
            <p>Notwithstanding anything in this Section 18 to the contrary, either party may: (a) bring an individual claim in small claims court for Disputes that qualify for small claims court jurisdiction in the applicable jurisdiction (Laramie County, Wyoming for US Disputes; or the applicable small claims court in User's province for Canadian Disputes), provided the claim remains in small claims court and does not seek class or representative relief; and (b) seek emergency injunctive, preliminary injunctive, or other interim equitable relief from any court of competent jurisdiction, without first undergoing the informal resolution process, to prevent irreparable harm or to preserve the status quo pending arbitration.</p>
            <h3>18.10 Applicability to Canadian Users</h3>
            <p>Doable and Canadian Users agree that the arbitration provisions of this Section 18 are intended to comply with applicable Canadian law to the maximum extent possible. If any provision of this Section 18 is found to be unenforceable under applicable Canadian law, that provision shall be modified to the minimum extent necessary to make it enforceable, or severed if modification is not possible, without affecting the remaining provisions.</p>
            <h3>18.11 Confidentiality of Arbitration</h3>
            <p>Except as required by law, both parties agree to maintain the confidentiality of all arbitration proceedings, awards, and settlements, including all documents submitted and all testimony given in connection with arbitration. This confidentiality obligation does not prevent either party from disclosing arbitration proceedings or awards to its legal advisors, accountants, or insurers on a need-to-know basis, or as required by applicable law or regulatory requirement.</p>
        </section>

        <!-- SECTION 19 -->
        <section class="terms-section" id="s19">
            <h2><span class="sec-num">19</span> Governing Law and Jurisdiction</h2>
            <h3>19.1 Governing Law — US Users</h3>
            <p>This Agreement and all Disputes arising out of or relating to this Agreement or the Services shall be governed by and construed in accordance with the laws of the State of Wyoming, United States of America, without regard to its conflict-of-laws provisions, and without regard to the United Nations Convention on Contracts for the International Sale of Goods (which is expressly excluded). Subject to the mandatory arbitration provisions of Section 18, any Dispute not subject to arbitration shall be brought exclusively in the state courts of Laramie County, Wyoming, or the United States District Court for the District of Wyoming, and each party irrevocably submits to the personal jurisdiction of those courts for such purpose and waives any objection to the laying of venue in those courts on the basis of inconvenient forum.</p>
            <h3>19.2 Governing Law — Canadian Users Outside Quebec</h3>
            <p>For Canadian Users located outside the Province of Quebec, this Agreement shall be governed by and construed in accordance with the laws of the Province of Ontario and the applicable federal laws of Canada, without regard to conflict-of-laws principles. Subject to Section 18, any Dispute not subject to arbitration shall be brought in the courts of the Province of Ontario, and each party irrevocably submits to the jurisdiction of those courts for such purpose.</p>
            <h3>19.3 Governing Law — Quebec Users</h3>
            <p>For Users located in the Province of Quebec, this Agreement shall be governed by and construed in accordance with the laws of the Province of Quebec and the applicable federal laws of Canada, without prejudice to any mandatory consumer protection provisions of Quebec law that cannot be waived by agreement. Subject to Section 18 and mandatory Quebec law, any Dispute not subject to arbitration shall be brought in the courts of the Province of Quebec.</p>
            <h3>19.4 Injunctive Relief</h3>
            <p>Notwithstanding the foregoing, Doable reserves the right to seek emergency injunctive or equitable relief in any court of competent jurisdiction in any jurisdiction to protect its Intellectual Property Rights or confidential information, or to prevent any breach of this Agreement that would cause irreparable harm.</p>
        </section>

        <!-- SECTION 20 -->
        <section class="terms-section" id="s20">
            <h2><span class="sec-num">20</span> Term and Termination</h2>
            <h3>20.1 Term</h3>
            <p>This Agreement begins on the date you first accept these Terms or access the Services (whichever is earlier) and continues until your Account is terminated in accordance with this Section 20.</p>
            <h3>20.2 Termination for Convenience by Doable</h3>
            <p>Doable may terminate this Agreement and User's access to the Services for any reason or no reason upon thirty (30) calendar days' advance written notice to User's registered email address. In the event of such termination for convenience, Doable will: (a) provide User with a final thirty (30) day period to export User Data; and (b) refund any prepaid subscription fees for the unused days of the then-current Subscription Period on a pro-rata basis to the payment method on file.</p>
            <h3>20.3 Termination for Cause by Doable</h3>
            <p>Doable may, in its sole discretion, immediately suspend or terminate User's Account and access to the Services, without prior notice and without obligation to refund any fees paid, upon the occurrence of any of the following:</p>
            <ol class="lettered">
                <li>User's material breach of any provision of this Agreement (including failure to pay fees when due, violation of the Acceptable Use Policy, or breach of the Intellectual Property provisions), which breach, if capable of being cured, remains uncured for five (5) calendar days after Doable provides written notice of the breach;</li>
                <li>User's breach of the Acceptable Use Policy in a manner that causes or risks harm to Doable, other users, or third parties;</li>
                <li>Doable's determination that User has provided false or fraudulent information in connection with Account registration or use;</li>
                <li>Doable's receipt of a complaint, regulatory demand, or legal proceeding arising from User's use of the Services; or</li>
                <li>Doable's reasonable belief that User's continued access to the Services poses a security risk to Doable or other users.</li>
            </ol>
            <p>Following any termination for cause, User shall remain responsible for all fees and charges incurred through the termination date.</p>
            <h3>20.4 Termination by User</h3>
            <p>User may cancel its Account and terminate this Agreement at any time by following the cancellation instructions set forth in Section 6 or by contacting Doable at Info@doable.net (Subject: Account Cancellation). Termination by User shall become effective at the end of the then-current paid Subscription Period. No refunds will be issued for fees paid for the current Subscription Period, except as provided in Sections 5.8 and 6.4.</p>
            <h3>20.5 Automatic Termination for Insolvency</h3>
            <p>To the extent permitted by applicable bankruptcy and insolvency law, this Agreement shall terminate automatically, without the requirement of notice, upon the occurrence of any of the following with respect to either party: (a) a general assignment for the benefit of creditors; (b) the filing of a voluntary petition in bankruptcy; (c) the adjudication of the party as bankrupt pursuant to an involuntary petition; (d) the appointment of a temporary or permanent receiver, trustee, or custodian for all or a substantial part of the party's assets; or (e) the party's general inability to pay its debts as they become due.</p>
            <h3>20.6 Effect of Termination</h3>
            <p>Upon any termination of this Agreement, regardless of the cause:</p>
            <ol class="lettered">
                <li>all licenses granted to User under this Agreement immediately and automatically terminate without further action by either party;</li>
                <li>User must immediately cease all access to and use of the Services and remove any Doable software from User's devices;</li>
                <li>all outstanding fees and charges become immediately due and payable;</li>
                <li>except upon termination for cause involving fraud or criminal conduct, User shall have ninety (90) calendar days from the effective date of termination to export User Data through Account Settings or by contacting Info@doable.net (Subject: Data Export Request — Account Termination);</li>
                <li>after the ninety (90)-day export window (or immediately upon termination for cause involving fraud or criminal conduct), Doable will delete or irreversibly anonymize User Data in accordance with Section 10.5; and</li>
                <li>each party's rights and obligations accrued prior to termination shall survive.</li>
            </ol>
            <h3>20.7 Survival</h3>
            <p>The following Sections shall survive the termination of this Agreement for any reason and shall remain in full force and effect: Section 1 (Definitions), Sections 4.3–4.5 (Intellectual Property Restrictions and Reservation), Section 5.5 (Non-Refundable Fees), Sections 9.1–9.4 (User Data Responsibility and License), Section 10 (Privacy), Section 12 (Intellectual Property), Section 15 (Disclaimer of Warranties), Section 16 (Limitation of Liability), Section 17 (Indemnification), Section 18 (Dispute Resolution and Arbitration), Section 19 (Governing Law), Section 20.6 (Effect of Termination), Section 20.7 (Survival), Section 21 (Export Controls and Sanctions Compliance), and Section 24 (General Provisions).</p>
        </section>

        <!-- SECTION 21 -->
        <section class="terms-section" id="s21">
            <h2><span class="sec-num">21</span> Export Controls and Sanctions Compliance</h2>
            <h3>21.1 Compliance Obligation</h3>
            <p>User represents and warrants that:</p>
            <ol class="lettered">
                <li>User is not located in, organized under the laws of, or a national or resident of any country or territory that is subject to comprehensive US Office of Foreign Assets Control (OFAC) sanctions, embargo, or export restrictions, or Canadian economic sanctions programs administered by Global Affairs Canada or the Department of Finance Canada, including without limitation, Cuba, Iran, North Korea, Syria, and the Crimea, Donetsk, or Luhansk regions of Ukraine;</li>
                <li>User is not identified on, and is not 50% or more owned or controlled by any entity identified on, any US or Canadian denied persons, debarred parties, sanctions, or restricted parties list, including without limitation, OFAC's Specially Designated Nationals and Blocked Persons List, the US Commerce Department's Entity List, the US State Department's AECA Debarred List, or any equivalent Canadian list; and</li>
                <li>User will not use the Services in any manner that would violate applicable US export control laws (including the Export Administration Regulations, 15 C.F.R. Parts 730–774) or applicable Canadian export control and sanctions laws (including the Export and Import Permits Act, R.S.C. 1985, c. E-19, and the Special Economic Measures Act, S.C. 1992, c. 17).</li>
            </ol>
            <h3>21.2 Ongoing Obligation</h3>
            <p>User's representations in Section 21.1 are ongoing and User must notify Doable immediately at Info@doable.net (Subject: Export Controls and Sanctions Notice) if User's status under Section 21.1 changes. Doable may immediately suspend or terminate User's Account without notice or liability upon a reasonable belief that User is in violation of this Section 21.</p>
        </section>

        <!-- SECTION 22 -->
        <section class="terms-section" id="s22">
            <h2><span class="sec-num">22</span> Modifications to This Agreement</h2>
            <h3>22.1 Doable's Right to Modify</h3>
            <p>Doable reserves the right to modify, update, or revise this Agreement at any time in its sole discretion. Modifications to this Agreement may be made to address changes in applicable law, new or modified Services features, Doable's business practices, or industry standards.</p>
            <h3>22.2 Notice of Modifications</h3>
            <p>Doable will provide advance notice of modifications to this Agreement as follows:</p>
            <ol class="lettered">
                <li><strong>Material Modifications</strong> — changes that materially affect User's rights or obligations, including changes to the arbitration provision, class action waiver, limitation of liability, or governing law — will be communicated by email to User's registered email address at least thirty (30) calendar days before the effective date of the modification.</li>
                <li><strong>Non-Material Modifications</strong> — administrative corrections, clarifications, or non-substantive changes — may be made upon fourteen (14) calendar days' notice by posting to doable.net.</li>
            </ol>
            <h3>22.3 Acceptance of Modifications</h3>
            <p>Your continued access to or use of the Services after the effective date of any modification to this Agreement constitutes your binding acceptance of the modified Agreement. If you do not agree to any modification, you must discontinue all use of the Services and cancel your Account before the modification takes effect. If you cancel your Account as a result of a material modification to this Agreement, Doable will refund any prepaid and unused subscription fees on a pro-rata basis.</p>
            <h3>22.4 Modification of Arbitration Provision</h3>
            <p>Any modification to the arbitration provision in Section 18 shall not apply to any Dispute for which the arbitration process has already been initiated, or of which Doable has received a Dispute Notice, prior to the effective date of the modification.</p>
        </section>

        <!-- SECTION 23 -->
        <section class="terms-section" id="s23">
            <h2><span class="sec-num">23</span> Force Majeure</h2>
            <h3>23.1 Excused Performance</h3>
            <p>Doable shall not be in default or breach of this Agreement, and shall not be liable to User or any third party, for any delay, failure, or interruption in the performance of its obligations under this Agreement arising directly or indirectly from causes beyond Doable's reasonable control, including but not limited to: acts of God; natural disasters (including earthquakes, floods, hurricanes, tornadoes, and wildfires); pandemics, epidemics, or public health emergencies; acts of war, terrorism, or civil unrest; governmental actions, embargoes, sanctions, or regulations; labor strikes or disputes not involving Doable's employees; cybersecurity incidents (including ransomware attacks, distributed denial-of-service attacks, advanced persistent threats, and other malicious cyberattacks); failures, outages, or service interruptions of third-party cloud computing providers, data centers, telecommunications networks, or internet service providers; supply chain disruptions; and any other event or circumstance beyond Doable's reasonable control (each, a "Force Majeure Event").</p>
            <h3>23.2 Notice and Mitigation</h3>
            <p>Upon the occurrence of a Force Majeure Event, Doable will promptly notify User at User's registered email address, describing the nature and expected duration of the Force Majeure Event, and will use commercially reasonable efforts to resume performance of its obligations as soon as reasonably practicable. Doable's performance obligations shall be suspended for the duration of the Force Majeure Event to the extent necessary.</p>
            <h3>23.3 Extended Force Majeure</h3>
            <p>If a Force Majeure Event continues for more than sixty (60) consecutive days, either party may terminate this Agreement upon written notice to the other party, and Doable will refund any prepaid subscription fees for periods during which the Services were unavailable on a pro-rata basis.</p>
        </section>

        <!-- SECTION 24 -->
        <section class="terms-section" id="s24">
            <h2><span class="sec-num">24</span> General Provisions</h2>
            <h3>24.1 Assignment</h3>
            <p>User may not assign, transfer, delegate, or sublicense this Agreement, or any of User's rights or obligations hereunder, without Doable's prior written consent. Any purported assignment without such consent shall be null and void and of no force or effect. Doable may assign or transfer this Agreement and any of its rights and obligations hereunder, in whole or in part, without User's consent, in connection with: (a) a merger, acquisition, corporate reorganization, or change of control involving Doable; (b) a sale of all or substantially all of Doable's assets or business; or (c) a corporate restructuring or financing transaction. Doable will provide notice of any such assignment to User's registered email address. This Agreement shall be binding upon and shall inure to the benefit of the parties and their respective permitted successors and assigns.</p>
            <h3>24.2 Entire Agreement</h3>
            <p>This Agreement, together with Doable's Privacy Policy (https://doable.net/privacy-policy), any applicable order forms, subscription confirmations, and any Data Processing Addendum executed between the parties, constitutes the entire agreement between the parties with respect to the subject matter hereof and supersedes and replaces all prior and contemporaneous agreements, representations, warranties, statements, negotiations, and understandings between the parties, whether oral or written, regarding the Services. In the event of any conflict between this Agreement and any other incorporated document, this Agreement shall control unless the other document expressly states that it supersedes a specific provision of this Agreement, and except as provided in Section 10.1 with respect to the handling of Personal Information.</p>
            <h3>24.3 Severability</h3>
            <p>If any provision of this Agreement is held by a court or arbitrator of competent jurisdiction to be invalid, illegal, unenforceable, or void under applicable law, that provision shall be: (a) modified to the minimum extent necessary to make it enforceable, while preserving its original intent to the maximum extent possible; or (b) if modification is not possible, severed from this Agreement, in either case without affecting the enforceability of the remaining provisions, which shall remain in full force and effect.</p>
            <h3>24.4 Waiver</h3>
            <p>No failure, delay, or omission by either party in exercising any right, power, or remedy under this Agreement shall operate as a waiver of that right, power, or remedy. No single or partial exercise of any right, power, or remedy shall preclude any further exercise of that right, power, or remedy or the exercise of any other right, power, or remedy. All waivers must be made in writing and signed by an authorized representative of the waiving party to be effective.</p>
            <h3>24.5 Independent Contractors</h3>
            <p>The parties to this Agreement are independent contractors. Nothing in this Agreement shall be construed to create a partnership, joint venture, agency, employment relationship, franchise, or fiduciary relationship between Doable and User. Neither party has the authority to bind the other, to make representations on behalf of the other, or to incur any obligation on behalf of the other.</p>
            <h3>24.6 No Third-Party Beneficiaries</h3>
            <p>Except as expressly provided in Section 13.3 with respect to Apple Inc. and in Section 17 with respect to the Doable Indemnitees, this Agreement is for the sole benefit of the parties and their respective permitted successors and assigns, and nothing in this Agreement, express or implied, is intended to or shall confer upon any other person or entity any legal or equitable right, benefit, or remedy of any nature whatsoever under or by reason of this Agreement.</p>
            <h3>24.7 Notices</h3>
            <ol class="lettered">
                <li><strong>Notices to Doable.</strong> All legal notices required or permitted under this Agreement must be sent in writing to Doable at: Doable LLC, Attn: Legal, 1712 Pioneer Ave., Suite 783, Cheyenne, Wyoming 82001, or by email to Info@doable.net (Subject: Legal Notice — followed by a brief description of the matter, for example, "Legal Notice — Breach of Agreement"), with delivery confirmation requested. Notices by email are effective upon Doable's written confirmation of receipt. Notices by mail are effective upon delivery as shown by the carrier's delivery confirmation.</li>
                <li><strong>Notices to User.</strong> All notices from Doable to User will be sent to the email address associated with User's Account. User is responsible for ensuring that the email address associated with User's Account is current and functional. Notices are effective when sent to the registered email address, regardless of whether User actually receives them.</li>
            </ol>
            <h3>24.8 Headings and Interpretation</h3>
            <p>Section headings are included for convenience only and shall have no legal effect on the interpretation of this Agreement. The words "include," "includes," and "including" are deemed followed by "without limitation." Unless the context otherwise requires, references to the singular include the plural and vice versa.</p>
            <h3>24.9 Governing Language</h3>
            <p>The parties have agreed that this Agreement and all related notices, documents, and communications shall be drafted, executed, and interpreted exclusively in the English language. Any translation of this Agreement into another language is provided for convenience only and shall have no legal effect. In the event of any conflict between the English version and any translation, the English version shall control.</p>
            <p>For Quebec Users (required under the Charter of the French Language, CQLR c C-11): <em>Les parties ont demandé et consenti à ce que cette convention ainsi que tous les avis, documents et communications qui s'y rapportent soient rédigés et interprétés exclusivement en langue anglaise.</em></p>
            <h3>24.10 Accessibility</h3>
            <p>Doable is committed to making the Services accessible to individuals with disabilities. Doable endeavors to design and develop the Services in compliance with the Web Content Accessibility Guidelines (WCAG 2.1) and, where applicable, the Accessibility for Ontarians with Disabilities Act (AODA) and the Accessible Canada Act. If you experience an accessibility barrier while using the Services or require an accommodation to access the Services, please contact Doable at Info@doable.net (Subject: Accessibility Accommodation Request). Doable will make commercially reasonable efforts to address accessibility concerns in a timely manner.</p>
            <h3>24.11 Counterparts and Electronic Signatures</h3>
            <p>This Agreement may be executed electronically. Electronic acceptance of these Terms (through clicking "I Agree" or similar mechanisms, or by using the Services) shall be deemed valid and legally binding to the same extent as a handwritten signature under applicable law, including the Wyoming Uniform Electronic Transactions Act (Wyo. Stat. § 40-21-101 et seq.), the US Electronic Signatures in Global and National Commerce Act (E-SIGN Act, 15 U.S.C. § 7001 et seq.), and Canada's Personal Information Protection and Electronic Documents Act (PIPEDA) and applicable provincial electronic commerce legislation.</p>
            <h3>24.12 Taxes and Government Compliance</h3>
            <p>User shall be solely responsible for: (a) all federal, state, provincial, and local taxes, assessments, levies, and similar charges imposed on User or User's use of the Services, other than taxes assessed on Doable's net income as provided in Section 5.4; and (b) obtaining all necessary permits, licenses, approvals, and authorizations required for User's operation of its business and use of the Services under applicable law.</p>
            <h3>24.13 Marketing References</h3>
            <p>Doable will not reference User's name, company name, or logo as a customer in Doable's marketing materials, website, case studies, investor presentations, or promotional content without User's prior written consent. User may grant consent by emailing Info@doable.net (Subject: Marketing Reference Consent). User may revoke any granted consent at any time by emailing Info@doable.net (Subject: Marketing Reference Consent Revocation), and Doable will remove the reference within thirty (30) days.</p>
            <h3>24.14 US Government End Users</h3>
            <p>If User is a US Government entity or the Services are being accessed by or on behalf of a US Government entity, the Services constitute "commercial computer software" and "commercial computer software documentation" as such terms are defined in applicable government regulations. Any use, modification, reproduction, release, performance, display, disclosure, or distribution of the Services by any US Government entity is subject solely to the terms of this Agreement.</p>
        </section>

        <!-- SECTION 25 -->
        <section class="terms-section contact-box" id="s25">
            <h2><span class="sec-num">25</span> Contact Information and Notices</h2>
            <p>For all inquiries, legal notices, and communications related to this Agreement, please contact Doable LLC using the contact information below. All email correspondence is directed to the single address Info@doable.net; to ensure prompt routing, enter the Subject line specified for the applicable topic exactly as shown in the table below.</p>
            <div class="contact-grid">
                <div>
                    <div class="label">Company</div>
                    <div class="value">Doable LLC (d/b/a Doable.net)</div>
                </div>
                <div>
                    <div class="label">Mailing Address</div>
                    <div class="value">1712 Pioneer Ave., Suite 783, Cheyenne, Wyoming 82001, USA</div>
                </div>
                <div>
                    <div class="label">Email</div>
                    <div class="value">Info@doable.net</div>
                </div>
                <div>
                    <div class="label">Website</div>
                    <div class="value">https://doable.net</div>
                </div>
            </div>
            <p style="margin-top:16px;">Mail correspondence that is not a formal legal notice under Section 24.7 may be sent to Doable LLC, Attn: Customer Support, 1712 Pioneer Ave., Suite 783, Cheyenne, Wyoming 82001, USA. Doable recommends email as the fastest and most reliable method of contact.</p>
        </section>

        <!-- ACKNOWLEDGMENT -->
        <section class="terms-section acknowledgment">
            <h2><span class="sec-num">✓</span> Acknowledgment</h2>
            <p>BY ACCESSING OR USING THE SERVICES IN ANY MANNER, YOU REPRESENT THAT YOU HAVE READ THIS AGREEMENT IN ITS ENTIRETY, THAT YOU UNDERSTAND ALL OF ITS TERMS, THAT YOU HAVE HAD THE OPPORTUNITY TO SEEK INDEPENDENT LEGAL ADVICE, AND THAT YOU AGREE TO BE LEGALLY BOUND BY ALL OF ITS TERMS AND CONDITIONS AS OF THE DATE OF FIRST ACCESS OR ACCEPTANCE. IF YOU ARE ACCEPTING ON BEHALF OF A COMPANY OR ORGANIZATION, YOU REPRESENT AND WARRANT THAT YOU HAVE FULL AUTHORITY TO BIND THAT COMPANY OR ORGANIZATION TO THIS AGREEMENT.</p>
            <p style="margin-top:16px; color:#9CA3AF; font-size:12px;">Doable LLC · 1712 Pioneer Ave., Suite 783 · Cheyenne, Wyoming 82001<br>Info@doable.net | doable.net · Terms of Use and Service Agreement — Version effective October 1, 2026 · Prior versions superseded in their entirety.</p>
        </section>

        <!-- Doc footer -->
        <div class="terms-doc-footer">
            <div class="page-num">Page 31 of 31 | © 2026 Doable LLC. All rights reserved.</div>
            <div>End of Agreement</div>
        </div>

    </article>

    <script>
        document.querySelectorAll('.terms-nav a').forEach(a => {
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