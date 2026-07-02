<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_section = basename(dirname($_SERVER['PHP_SELF']));

$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER`  = " . $_SESSION['PK_ACCOUNT_MASTER']);
?>

<div class="sidebar">
    <div class="sidebar-section-title">Operations</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link sidebar-link <?= $current_page == 'all_corporations.php' || $current_page == 'corporation.php' ? 'active' : '' ?>" href="all_corporations.php">
            <i class="bi bi-building"></i> Corporations
        </a>
        <?php if ($account_data->fields['IS_CONCIERGE'] == 1) {  ?>
            <a class="nav-link sidebar-link <?= $current_page == 'concierge_settings.php' ? 'active' : '' ?>" href="concierge_settings.php">
                <i class="bi bi-gear"></i> Concierge Settings
            </a>
            <a class="nav-link sidebar-link <?= $current_page == 'faq_settings.php' ? 'active' : '' ?>" href="faq_settings.php">
                <i class="bi bi-question-circle"></i> FAQ Settings
            </a>
        <?php } ?>
        <a class="nav-link sidebar-link <?= $current_page == 'all_locations.php' || $current_page == 'location.php' ? 'active' : '' ?>" href="all_locations.php">
            <i class="bi bi-geo-alt"></i> Locations
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_users.php' || $current_page == 'user.php' ? 'active' : '' ?>" href="all_users.php">
            <i class="bi bi-person"></i> Users
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_follow_ups.php' || $current_page == 'add_follow_up.php' ? 'active' : '' ?>" href="all_follow_ups.php">
            <i class="bi bi-journal-text"></i> Follow Ups
        </a>
    </nav>

    <div class="sidebar-section-title">Services</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link sidebar-link <?= $current_page == 'all_scheduling_codes.php' || $current_page == 'add_scheduling_codes.php' ? 'active' : '' ?>" href="all_scheduling_codes.php">
            <i class="bi bi-box-arrow-up-right"></i> Scheduling Codes
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_service_codes.php' || $current_page == 'service_codes.php' ? 'active' : '' ?>" href="all_service_codes.php">
            <i class="bi bi-handbag"></i> Services
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_packages.php' || $current_page == 'package.php' ? 'active' : '' ?>" href="all_packages.php">
            <i class="bi bi-box-seam"></i> Packages
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_document_library.php' || $current_page == 'document_library.php' ? 'active' : '' ?>" href="all_document_library.php">
            <i class="bi bi-file-earmark-text"></i> Document Library
        </a>
    </nav>

    <div class="sidebar-section-title">Other</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link sidebar-link <?= $current_page == 'all_gift_certificates.php' || $current_page == 'gift_certificate.php' ? 'active' : '' ?>" href="all_gift_certificates.php">
            <i class="bi bi-gift"></i> Gift Certificates
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_gift_certificate_setup.php' || $current_page == 'gift_certificate_setup.php' ? 'active' : '' ?>" href="all_gift_certificate_setup.php">
            <i class="bi bi-sliders2"></i> Gift Certificate Setup
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_event_types.php' || $current_page == 'event_type.php' ? 'active' : '' ?>" href="all_event_types.php">
            <i class="bi bi-star"></i> Event Types
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_inquiry_methods.php' || $current_page == 'add_inquiry_method.php' ? 'active' : '' ?>" href="all_inquiry_methods.php">
            <i class="bi bi-book"></i> Inquiry Methods
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_lead_status.php' || $current_page == 'lead_status.php' ? 'active' : '' ?>" href="all_lead_status.php">
            <i class="bi bi-dot text-secondary"></i> Lead Status
        </a>
    </nav>

    <div class="sidebar-section-title">Communication</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link sidebar-link <?= $current_page == 'all_email_templates.php' || $current_page == 'email_template.php' ? 'active' : '' ?>" href="all_email_templates.php">
            <i class="bi bi-envelope"></i> Email Templates
        </a>
    </nav>

    <div class="sidebar-section-title">Logs</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link sidebar-link <?= $current_page == 'sms_logs.php' ? 'active' : '' ?>" href="sms_logs.php">
            <i class="bi bi-chat-left-text"></i> SMS Logs
        </a>
    </nav>
</div>