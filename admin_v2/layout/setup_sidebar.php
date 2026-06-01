<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_section = basename(dirname($_SERVER['PHP_SELF']));
?>

<div class="sidebar">
    <div class="sidebar-section-title">Operations</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link sidebar-link <?= $current_page == 'all_corporations_new.php' ? 'active' : '' ?>" href="all_corporations_new.php">
            <i class="bi bi-building"></i> Corporations
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_locations_new.php' ? 'active' : '' ?>" href="all_locations_new.php">
            <i class="bi bi-geo-alt"></i> Locations
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_users_new.php' ? 'active' : '' ?>" href="all_users_new.php">
            <i class="bi bi-person"></i> Users
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_followups_new.php' ? 'active' : '' ?>" href="all_followups_new.php">
            <i class="bi bi-journal-text"></i> Follow Ups
        </a>
    </nav>

    <div class="sidebar-section-title">Services</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link sidebar-link <?= $current_page == 'all_scheduling_codes_new.php' ? 'active' : '' ?>" href="all_scheduling_codes_new.php">
            <i class="bi bi-box-arrow-up-right"></i> Scheduling Codes
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_service_codes_new.php' ? 'active' : '' ?>" href="all_service_codes_new.php">
            <i class="bi bi-handbag"></i> Services
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_packages_new.php' ? 'active' : '' ?>" href="all_packages_new.php">
            <i class="bi bi-box-seam"></i> Packages
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_document_library_new.php' ? 'active' : '' ?>" href="all_document_library_new.php">
            <i class="bi bi-file-earmark-text"></i> Document Library
        </a>
    </nav>

    <div class="sidebar-section-title">Other</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link sidebar-link <?= $current_page == 'all_gift_certificates_new.php' ? 'active' : '' ?>" href="all_gift_certificates_new.php">
            <i class="bi bi-gift"></i> Gift Certificates
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_gift_certificate_setup_new.php' ? 'active' : '' ?>" href="all_gift_certificate_setup_new.php">
            <i class="bi bi-sliders2"></i> Gift Certificate Setup
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_event_types_new.php' ? 'active' : '' ?>" href="all_event_types_new.php">
            <i class="bi bi-star"></i> Event Types
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_inquiry_method_new.php' ? 'active' : '' ?>" href="all_inquiry_method_new.php">
            <i class="bi bi-book"></i> Inquiry Method
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'all_lead_status_new.php' ? 'active' : '' ?>" href="all_lead_status_new.php">
            <i class="bi bi-dot text-secondary"></i> Lead Status
        </a>
    </nav>

    <div class="sidebar-section-title">Logs</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link sidebar-link <?= $current_page == 'sms_logs_new.php' ? 'active' : '' ?>" href="sms_logs_new.php">
            <i class="bi bi-chat-left-text"></i> SMS Logs
        </a>
    </nav>
</div>