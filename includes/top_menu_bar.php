<?php
global $db;
if (!empty($_GET['view'])) {
    $view = $_GET['view'];
} else {
    $view = 'list';
}
?>

<?php
$mail_url = parse_url($_SERVER['REQUEST_URI']);
$url_array = explode("/", $mail_url['path']);
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $current_address = $url_array[3];
} else {
    $current_address = $url_array[2];
}
?>
<style>
    #top_bar {
        z-index: 50;
    }

    #navbarDropdownMenuLink {
        z-index: 500;
    }

    .menu-list {
        list-style-type: none;
        margin-left: -30px;
    }

    .menu-list li {
        margin: 10px;
    }

    .new-top-menu a.dropdown-item {
        padding: 5px 10px;
        font-size: 14px;
    }

    .new-top-menu a.dropdown-item:hover {
        background: #f4f4f4;
    }

    .new-top-menu .dropdown-item.active,
    .new-top-menu .dropdown-item:active {
        color: #000;
        text-decoration: none;
        background-color: #f4f4f4;
    }

    nav a:active {
        text-decoration: none;
        border-bottom: none;
        outline: none;
    }

    nav .active {
        border-bottom: none;
        box-shadow: none;
        text-decoration: none;
    }
</style>

<div id="top_bar" class="container-fluid p-0 nav-top-new topbar">
    <div class="row">
        <div class="d-flex flex-column flex-md-row align-items-center py-0 px-4 bg-white border-bottom box-shadow justify-content-end">
            <?php /*if($_SESSION['PK_ROLES'] == 2) { */ ?><!--
            <div class="col-md-4 new-top-menu">
                <nav class="navbar navbar-expand-lg px-2 py-1">
                    <div class="collapse navbar-collapse" id="navbarNavDropdown">
                        <ul class="navbar-nav">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    General
                                </a>
                                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                    <a class="dropdown-item" href="business_profile.php">Business Profile</a>
                                    <a class="dropdown-item" href="all_locations.php">Locations</a>
                                    <a class="dropdown-item" href="all_users.php">Users</a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Services
                                </a>
                                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                    <a class="dropdown-item" href="all_services.php">Services</a>
                                    <a class="dropdown-item" href="all_packages.php">Packages</a>
                                    <a class="dropdown-item" href="all_document_library.php">Document Library</a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Others
                                </a>
                                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                    <a class="dropdown-item" href="all_gift_certificates.php">Gift Certificate</a>
                                    <a class="dropdown-item" href="all_gift_certificate_setup.php">Gift Certificate Setup</a>
                                    <a class="dropdown-item" href="all_event_types.php">Event Types</a>
                                    <a class="dropdown-item" href="all_inquiry_methods.php">Inquiry Method</a>
                                    <a class="dropdown-item" href="all_scheduling_codes.php">Scheduling Codes</a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Communication
                                </a>
                                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                    <a class="dropdown-item" href="all_email_accounts.php">Email Accounts</a>
                                    <a class="dropdown-item" href="all_email_templates.php">Email Templates</a>
                                    <a class="dropdown-item" href="all_text_templates.php">Text Templates</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
            --><?php /*} */ ?>
            <nav class="my-2 my-md-0 mr-md-3 new-top-nav col-md-12">
                <ul id="sidebarnav" class="nav nav-pills" style="float: right;">
                    <?php if ($_SESSION['PK_ROLES'] == 1) { ?>
                        <li>
                            <a class="nav-link" href="../super_admin/all_accounts.php" aria-expanded="false">
                                <i class="ti-user"></i>
                                <span class="hide-menu">Accounts</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link" href="../super_admin/setup.php" aria-expanded="false">
                                <i class="ti-settings"></i>
                                <span class="hide-menu">Setup</span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if (!in_array($_SESSION['PK_ROLES'], [1, 4, 5])) { ?>
                        <li class="<?= (('all_schedules.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('all_schedules.php' === $current_address) ? 'active' : '') ?>" href="../admin/all_schedules.php" aria-expanded="false">
                                <i class="icon-calender"></i>
                                <span class="hide-menu">Calendar</span>
                            </a>
                        </li>
                        <li class="<?= (('appointment_list.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('appointment_list.php' === $current_address) ? 'active' : '') ?>" href="../admin/appointment_list.php" aria-expanded="false">
                                <i class="icon-list"></i>
                                <span class="hide-menu">Appointments</span>
                            </a>
                        </li>
                        <li class="<?= (('to_do_list.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('to_do_list.php' === $current_address) ? 'active' : '') ?>" href="../admin/to_do_list.php" aria-expanded="false">
                                <i class="icon-notebook"></i>
                                <span class="hide-menu">To-Do</span>
                            </a>
                        </li>
                        <li class="<?= (('all_leads.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('all_leads.php' === $current_address) ? 'active' : '') ?>" href="../admin/all_leads.php" aria-expanded="false">
                                <i class="icon-people"></i>
                                <span class="hide-menu">Leads</span>
                            </a>
                        </li>
                        <li class="<?= (('all_customers.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('all_customers.php' === $current_address) ? 'active' : '') ?>" href="../admin/all_customers.php" aria-expanded="false">
                                <i class="icons-User"></i>
                                <span class="hide-menu">Customers</span>
                            </a>
                        </li>
                        <li class="<?= (('all_enrollments.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('all_enrollments.php' === $current_address) ? 'active' : '') ?>" href="../admin/all_enrollments.php" aria-expanded="false">
                                <i class="icon-note"></i>
                                <span class="hide-menu">Enrollments</span>
                            </a>
                        </li>
                        <li class="<?= (('all_events.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('all_events.php' === $current_address) ? 'active' : '') ?>" href="../admin/all_events.php" aria-expanded="false">
                                <i class="ti-calendar"></i>
                                <span class="hide-menu">Events</span>
                            </a>
                        </li>
                        <li class="<?= (('operations.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('operations.php' === $current_address) ? 'active' : '') ?>" href="../admin/operations.php" aria-expanded="false">
                                <i class="ti-layers-alt"></i>
                                <span class="hide-menu"><?= $operation_tab_title ?></span>
                            </a>
                        </li>
                        <li class="<?= (('reports.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('reports.php' === $current_address || 'business_reports.php' === $current_address || "service_provider_reports.php" === $current_address || "electronic_miscellaneous_reports.php" === $current_address || "enrollment_reports.php" === $current_address || "customer_summary_report.php" === $current_address || "student_mailing_list.php" === $current_address || "total_open_liability.php" === $current_address) ? 'active' : '') ?>" href="../admin/reports.php" aria-expanded="false">
                                <i class="ti-bar-chart"></i>
                                <span class="hide-menu">Reports</span>
                            </a>
                        </li>
                        <li class="<?= (('setup.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('setup.php' === $current_address || $current_address == "business_profile.php" || $current_address == "settings.php" || $current_address == "all_locations.php" || $current_address == "all_users.php" || $current_address == "deleted_customer.php" || $current_address == "all_service_codes.php" || $current_address == "all_packages.php" || $current_address == "all_scheduling_codes.php" || $current_address == "all_document_library.php" || $current_address == "all_interests.php" || $current_address == "all_skill_levels.php" || $current_address == "all_gift_certificates.php" || $current_address == "all_gift_certificate_setup.php" || $current_address == "all_event_types.php" || $current_address == "all_inquiry_methods.php" || $current_address == "all_email_accounts.php" || $current_address == "all_email_templates.php" || $current_address == "all_text_templates.php" || $current_address == "test_chat_gpt.php" || $current_address == "data_uploader.php" || $current_address == "all_products.php" || $current_address == "all_orders.php" || $current_address == "order_details.php") ? 'active' : '') ?>" href="../admin/setup.php" aria-expanded="false">
                                <i class="ti-settings"></i>
                                <span class="hide-menu">Setup</span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if ($_SESSION['PK_ROLES'] == 3) { ?>
                        <!--<li class="<?= (('all_enrollments.php' === $current_address) ? 'active' : '') ?>"> <a class="has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false"><i class="ti-layout-grid2"></i><span class="hide-menu">Setup</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="../super_admin/all_users.php">Users</a></li>
                            </ul>
                        </li>-->
                    <?php } ?>

                    <?php if ($_SESSION['PK_ROLES'] == 4) { ?>
                        <li>
                            <a class="nav-link <?= (('all_schedules.php' === $current_address) ? 'active' : '') ?>" href="../customer/all_schedules.php" aria-expanded="false">
                                <i class="icon-calender"></i>
                                <span class="hide-menu">Calendar</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link <?= (('appointment_list.php' === $current_address) ? 'active' : '') ?>" href="../customer/appointment_list.php" aria-expanded="false">
                                <i class="icon-list"></i>
                                <span class="hide-menu">Appointments</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link <?= (('all_products.php' === $current_address) ? 'active' : '') ?>" href="../customer/all_products.php" aria-expanded="false">
                                <b class="icons-Add-Cart"></b>
                                <span class="hide-menu">Shop</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link <?= (('all_gift_certificates.php' === $current_address) ? 'active' : '') ?>" href="../customer/all_gift_certificates.php" aria-expanded="false">
                                <i class="icons-Gift-Box"></i>
                                <span class="hide-menu">Gift Cards</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link <?= (('billing.php' === $current_address) ? 'active' : '') ?>" href="../customer/billing.php" aria-expanded="false">
                                <i class="icon-note"></i>
                                <span class="hide-menu">Enrollments</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link <?= (('accounts.php' === $current_address) ? 'active' : '') ?>" href="../customer/accounts.php" aria-expanded="false">
                                <i class="icons-Receipt"></i>
                                <span class="hide-menu">Accounts</span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if ($_SESSION['PK_ROLES'] == 5) { ?>
                        <li class="<?= (('all_schedules.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('all_schedules.php' === $current_address) ? 'active' : '') ?>" href="../admin/all_schedules.php" aria-expanded="false">
                                <i class="icon-calender"></i>
                                <span class="hide-menu">Calendar</span>
                            </a>
                        </li>
                        <li class="<?= (('appointment_list.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('appointment_list.php' === $current_address) ? 'active' : '') ?>" href="../admin/appointment_list.php" aria-expanded="false">
                                <i class="icon-calender"></i>
                                <span class="hide-menu">Appointments</span>
                            </a>
                        </li>
                        <li class="<?= (('all_customers.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('all_customers.php' === $current_address) ? 'active' : '') ?>" href="../admin/all_customers.php" aria-expanded="false">
                                <i class="icons-User"></i>
                                <span class="hide-menu">Customers</span>
                            </a>
                        </li>
                        <li class="<?= (('all_enrollments.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('all_enrollments.php' === $current_address) ? 'active' : '') ?>" href="../admin/all_enrollments.php" aria-expanded="false">
                                <i class="icon-note"></i>
                                <span class="hide-menu">Enrollments</span>
                            </a>
                        </li>
                        <li class="<?= (('operations.php' === $current_address) ? 'active' : '') ?>">
                            <a class="nav-link <?= (('operations.php' === $current_address) ? 'active' : '') ?>" href="../admin/operations.php" aria-expanded="false">
                                <i class="ti-layers-alt"></i>
                                <span class="hide-menu">Operations</span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </nav>
        </div>
    </div>
</div>