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
        <a class="nav-link sidebar-link <?= $current_page == 'followups' ? 'active' : '' ?>" href="../followups/">
            <i class="bi bi-journal-text"></i> Follow Ups
        </a>
    </nav>

    <div class="sidebar-section-title">Services</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link sidebar-link <?= $current_page == 'all_scheduling_codes_new.php' ? 'active' : '' ?>" href="all_scheduling_codes_new.php">
            <i class="bi bi-box-arrow-up-right"></i> Scheduling Codes
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'services' ? 'active' : '' ?>" href="../services/">
            <i class="bi bi-handbag"></i> Services
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'packages' ? 'active' : '' ?>" href="../packages/">
            <i class="bi bi-box-seam"></i> Packages
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'document-library' ? 'active' : '' ?>" href="../document-library/">
            <i class="bi bi-file-earmark-text"></i> Document Library
        </a>
    </nav>

    <div class="sidebar-section-title">Other</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link sidebar-link <?= $current_page == 'gift-certificates' ? 'active' : '' ?>" href="../gift-certificates/">
            <i class="bi bi-gift"></i> Gift Certificates
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'gift-certificate-setup' ? 'active' : '' ?>" href="../gift-certificate-setup/">
            <i class="bi bi-sliders2"></i> Gift Certificate Setup
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'event-types' ? 'active' : '' ?>" href="../event-types/">
            <i class="bi bi-star"></i> Event Types
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'inquiry-method' ? 'active' : '' ?>" href="../inquiry-method/">
            <i class="bi bi-book"></i> Inquiry Method
        </a>
        <a class="nav-link sidebar-link <?= $current_page == 'lead-status' ? 'active' : '' ?>" href="../lead-status/">
            <i class="bi bi-dot text-secondary"></i> Lead Status
        </a>
    </nav>

    <div class="sidebar-section-title">Logs</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link sidebar-link <?= $current_page == 'sms-logs' ? 'active' : '' ?>" href="../sms-logs/">
            <i class="bi bi-chat-left-text"></i> SMS Logs
        </a>
    </nav>
</div>