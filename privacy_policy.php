<?php

/** Privacy Policy page */
require_once __DIR__ . '/v2/includes/functions.php';
$page = 'privacy';
$page_title = 'Privacy Policy | ' . SITE_NAME;
$base = base_path();

include __DIR__ . '/v2/includes/header.php';
?>

<section class="gradient-mesh pt-32 pb-16 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto text-center">
        <div class="inline-block px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium mb-4">Privacy</div>
        <h1 class="text-premium-heading text-4xl md:text-5xl font-extrabold mb-4">Privacy <span class="gradient-text">Policy</span></h1>
        <p class="text-lg text-gray-600">Effective January 1, 2025</p>
    </div>
</section>

<section class="py-16 px-4 sm:px-6 lg:px-8 bg-white">
    <div class="max-w-4xl mx-auto">
        <div class="prose prose-lg prose-emerald max-w-none">
            <p class="lead text-gray-600 text-xl leading-relaxed mb-8">This Privacy Policy ("Policy") describes how Doable LLC ("Doable," "we," "our," or "us") collects, uses, and discloses your personal information when you use our website (doable.net), mobile application, and any related services (collectively, the "Services"). This Policy is incorporated into and subject to our <a href="<?= $base ?>/terms_of_use.php" class="text-emerald-600 hover:text-emerald-800 underline font-medium">Terms of Use</a>.</p>

            <p class="text-gray-600 leading-relaxed">By accessing or using the Services, you agree to the collection and use of information in accordance with this Policy. If you do not agree with this Policy, please do not use the Services.</p>

            <p class="text-gray-600 leading-relaxed">This Policy applies to all users of the Services, including account holders, visitors, and those who schedule meetings via the Services (collectively, "Users" or "you").</p>

            <div class="bg-amber-50 border-l-4 border-amber-400 p-6 my-8 rounded-r-lg">
                <p class="text-amber-800 font-semibold mb-0">We are committed to protecting your privacy and complying with applicable data protection laws worldwide, including but not limited to the California Consumer Privacy Act (CCPA) and other U.S. state privacy laws, the General Data Protection Regulation (GDPR) in the European Union, the Personal Information Protection and Electronic Documents Act (PIPEDA) in Canada, and other international privacy regulations. This Policy is designed to be binding in all jurisdictions where we operate, to the extent required by local laws.</p>
            </div>

            <!-- Information We Collect -->
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mt-12 mb-4 pb-2 border-b-2 border-emerald-100">Information We Collect</h2>
            <p class="text-gray-600 leading-relaxed">We collect information that identifies, relates to, describes, or could reasonably be linked to a particular individual or household ("Personal Information" or "Personal Data"). The types of Personal Information we may collect include:</p>

            <ul class="text-gray-600 leading-relaxed space-y-2 list-disc pl-6">
                <li><strong class="text-gray-800">Account Information:</strong> When you create an account, we collect your name, email address, password, and any other information you provide during registration.</li>
                <li><strong class="text-gray-800">Scheduling Information:</strong> Details related to appointments and meetings, such as dates, times, participant names, email addresses, and any notes or attachments you provide.</li>
                <li><strong class="text-gray-800">Contact Information:</strong> Phone numbers if provided for SMS notifications.</li>
                <li><strong class="text-gray-800">Usage Data:</strong> Information about how you interact with the Services, including IP address, browser type, device information, pages visited, and timestamps.</li>
                <li><strong class="text-gray-800">Payment Information:</strong> Billing details, such as credit card information, processed through third-party payment processors (we do not store full payment card details).</li>
                <li><strong class="text-gray-800">Communications:</strong> Any messages, feedback, or inquiries you send to us.</li>
                <li><strong class="text-gray-800">Cookies and Tracking Technologies:</strong> We use cookies, web beacons, and similar technologies to collect information about your browsing activities and preferences.</li>
            </ul>

            <p class="text-gray-600 leading-relaxed mt-4">We collect this information directly from you when you provide it, automatically as you use the Services, or from third parties (e.g., calendar integrations with your consent).</p>

            <!-- How We Use Your Information -->
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mt-12 mb-4 pb-2 border-b-2 border-emerald-100">How We Use Your Information</h2>
            <p class="text-gray-600 leading-relaxed">We use the Personal Information we collect for the following purposes:</p>

            <ul class="text-gray-600 leading-relaxed space-y-2 list-disc pl-6">
                <li>To provide and maintain the Services, including facilitating scheduling, sending notifications, and managing accounts.</li>
                <li>To process payments and manage subscriptions.</li>
                <li>To communicate with you, including sending transactional emails, reminders, and updates about the Services.</li>
                <li>To improve and personalize the Services based on usage data.</li>
                <li>To comply with legal obligations and enforce our Terms of Use.</li>
                <li>For security and fraud prevention.</li>
                <li>With your consent, for marketing purposes, such as sending promotional emails (you may opt-out at any time).</li>
            </ul>

            <p class="text-gray-600 leading-relaxed">We process your Personal Data based on legal grounds such as your consent, the performance of a contract, legitimate interests, or legal obligations, as required under applicable laws like the GDPR.</p>

            <p class="text-gray-600 leading-relaxed">We do not use your Personal Information for purposes beyond those described in this Policy without your consent.</p>

            <!-- Sharing Your Information -->
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mt-12 mb-4 pb-2 border-b-2 border-emerald-100">Sharing Your Information</h2>
            <p class="text-gray-600 leading-relaxed">We may share your Personal Information with:</p>

            <ul class="text-gray-600 leading-relaxed space-y-2 list-disc pl-6">
                <li><strong class="text-gray-800">Service Providers:</strong> Third parties that assist us in operating the Services, such as payment processors, email services, and analytics providers. These providers are contractually obligated to protect your information and act as data processors under GDPR where applicable.</li>
                <li><strong class="text-gray-800">Business Partners:</strong> With your consent, we may share information for integrated services (e.g., calendar syncing).</li>
                <li><strong class="text-gray-800">Legal Requirements:</strong> If required by law, subpoena, or to protect our rights, property, or safety.</li>
                <li><strong class="text-gray-800">Business Transfers:</strong> In connection with a merger, acquisition, or sale of assets.</li>
            </ul>

            <div class="bg-emerald-50 border-l-4 border-emerald-400 p-6 my-8 rounded-r-lg">
                <p class="text-emerald-800 mb-0">We do not sell your Personal Information to third parties. However, under the CCPA, certain disclosures may be considered a "sale" – please see the CCPA section below for details. For GDPR purposes, we ensure any sharing complies with data protection requirements.</p>
            </div>

            <!-- Data Security -->
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mt-12 mb-4 pb-2 border-b-2 border-emerald-100">Data Security</h2>
            <p class="text-gray-600 leading-relaxed">We implement reasonable security measures to protect your Personal Information from unauthorized access, use, or disclosure. However, no method of transmission over the Internet or electronic storage is 100% secure, so we cannot guarantee absolute security.</p>

            <!-- Your Rights and Choices -->
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mt-12 mb-4 pb-2 border-b-2 border-emerald-100">Your Rights and Choices</h2>
            <p class="text-gray-600 leading-relaxed">You have certain rights regarding your Personal Information, including:</p>

            <ul class="text-gray-600 leading-relaxed space-y-2 list-disc pl-6">
                <li><strong class="text-gray-800">Access:</strong> Request details about the Personal Information we hold about you.</li>
                <li><strong class="text-gray-800">Correction:</strong> Update inaccurate information.</li>
                <li><strong class="text-gray-800">Deletion:</strong> Request deletion of your Personal Information (subject to legal exceptions).</li>
                <li><strong class="text-gray-800">Opt-Out:</strong> Opt-out of marketing communications or certain data sharing.</li>
                <li><strong class="text-gray-800">Non-Discrimination:</strong> We will not discriminate against you for exercising these rights.</li>
            </ul>

            <p class="text-gray-600 leading-relaxed">To exercise these rights, contact us at <a href="mailto:support@doable.net" class="text-emerald-600 hover:text-emerald-800 underline font-medium">support@doable.net</a>. We may require verification of your identity.</p>

            <div class="bg-amber-50 border-l-4 border-amber-400 p-6 my-8 rounded-r-lg">
                <p class="text-amber-800 font-semibold mb-0"><strong>For CCPA Rights (California Residents):</strong> You have the right to know, delete, and opt-out of the sale of your Personal Information. We do not sell Personal Information, but if this changes, we will update this Policy and provide opt-out options.</p>
            </div>

            <p class="text-gray-600 leading-relaxed">Residents of other states may have similar rights under applicable laws (e.g., Virginia's CDPA, Colorado's CPA). We will comply with such laws as they apply.</p>

            <div class="bg-amber-50 border-l-4 border-amber-400 p-6 my-8 rounded-r-lg">
                <p class="text-amber-800 font-semibold mb-0"><strong>For International Users (e.g., GDPR Rights for EU/EEA Residents):</strong> You have rights to access, rectify, erase, restrict processing, data portability, and object to processing. We process data under lawful bases such as consent or legitimate interests. If you withdraw consent, it won't affect prior processing. Contact us or your local data protection authority for complaints.</p>
            </div>

            <p class="text-gray-600 leading-relaxed">Similar rights apply under other international laws, such as PIPEDA in Canada.</p>

            <!-- Children's Privacy -->
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mt-12 mb-4 pb-2 border-b-2 border-emerald-100">Children's Privacy</h2>
            <p class="text-gray-600 leading-relaxed">Our Services are not intended for individuals under 18 years of age. We do not knowingly collect Personal Information from children under 18. If we become aware of such collection, we will delete it.</p>

            <!-- International Transfers -->
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mt-12 mb-4 pb-2 border-b-2 border-emerald-100">International Transfers</h2>
            <p class="text-gray-600 leading-relaxed">Your information may be transferred to and processed in countries other than your own, including the United States. For transfers from the EU/EEA, UK, or other regions with strict data export rules, we use appropriate safeguards such as Standard Contractual Clauses, adequacy decisions, or other mechanisms to ensure compliance with laws like the GDPR.</p>

            <p class="text-gray-600 leading-relaxed">If you are located outside the United States, please be aware that your Personal Data will be transferred to the U.S., which may have different data protection standards.</p>

            <!-- Changes to This Policy -->
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mt-12 mb-4 pb-2 border-b-2 border-emerald-100">Changes to This Policy</h2>
            <p class="text-gray-600 leading-relaxed">We may update this Policy from time to time. We will notify you of significant changes by posting the new Policy on our website with a revised effective date. Your continued use of the Services after changes constitutes acceptance.</p>

            <!-- Contact Us -->
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mt-12 mb-4 pb-2 border-b-2 border-emerald-100">Contact Us</h2>
            <p class="text-gray-600 leading-relaxed">If you have questions about this Policy, contact us at <a href="mailto:support@doable.net" class="text-emerald-600 hover:text-emerald-800 underline font-medium">support@doable.net</a>.</p>

            <!-- SMS/Text Messaging Privacy -->
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mt-12 mb-4 pb-2 border-b-2 border-emerald-100">SMS/Text Messaging Privacy</h2>
            <div class="bg-emerald-50 border-l-4 border-emerald-400 p-6 my-8 rounded-r-lg">
                <p class="text-emerald-800 mb-0">We do not share mobile contact information with third parties or affiliates for marketing or promotional purposes. Information may be shared with subcontractors in support services, such as customer service. All other categories exclude text messaging originator opt-in data and consent; this information will not be shared with any third parties.</p>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/v2/includes/footer.php'; ?>