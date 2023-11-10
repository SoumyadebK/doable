<?php
global $db;
if (!empty($_GET['view'])) {
    $view = $_GET['view'];
} else {
    $view = 'list';
}
?>

<style>
    #top_bar {
        z-index: 50;
    }
    #navbarDropdownMenuLink {
        z-index: 500;
    }
    .menu-list{
        list-style-type: none;
        margin-left: -30px;
    }

    .menu-list li{
        margin: 10px;
    }
    .new-top-menu a.dropdown-item {
        padding: 5px 10px;
        font-size: 14px;
    }
    .new-top-menu a.dropdown-item:hover {
        background: #f4f4f4;
    }
    .new-top-menu .dropdown-item.active, .new-top-menu .dropdown-item:active {
        color: #000;
        text-decoration: none;
        background-color: #f4f4f4;
    }
</style>

<div id="top_bar" class="container-fluid p-0 nav-top-new topbar">
    <div class="row">
        <div class="d-flex flex-column flex-md-row align-items-center py-2 px-4 bg-white border-bottomm box-shadow justify-content-end">
            <?php if($_SESSION['PK_ROLES'] == 2) { ?>
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
            <?php } ?>
            <nav class="my-2 my-md-0 mr-md-3 new-top-nav col-md-8">
                <ul id="sidebarnav" style="float: right;">
                    <?php if($_SESSION['PK_ROLES'] == 1) { ?>
                        <li>
                            <a class="waves-effect waves-dark" href="../super_admin/all_accounts.php" aria-expanded="false">
                                <i class="ti-user"></i>
                                <span class="hide-menu">Accounts</span>
                            </a>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../super_admin/setup.php" aria-expanded="false">
                                <i class="ti-settings"></i>
                                <span class="hide-menu">Setup</span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if($_SESSION['PK_ROLES'] == 2) { ?>
                        <!--<li>
                            <?php /*if ($view=='list') { */?>
                            <a class="waves-effect waves-dark" href="../admin/all_schedules.php?view=table" aria-expanded="false">
                                <i class="icon-calender"></i>
                                <span class="hide-menu">Calendar</span>
                            </a>
                            <?php /*} elseif ($view=='table') { */?>
                            <a class="waves-effect waves-dark" href="../admin/all_schedules.php?view=list" aria-expanded="false">
                                <i class="icon-list"></i>
                                <span class="hide-menu">List</span>
                            </a>
                            <?php /*} */?>
                        </li>-->
                        <li>
                            <a class="waves-effect waves-dark" href="../admin/all_schedules.php?view=table" aria-expanded="false">
                                <i class="icon-calender"></i>
                                <span class="hide-menu">Calendar</span>
                            </a>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../admin/appointment_list.php" aria-expanded="false">
                                <i class="icon-list"></i>
                                <span class="hide-menu">List</span>
                            </a>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../admin/all_customers.php" aria-expanded="false">
                                <i class="icons-User"></i>
                                <span class="hide-menu">Customers</span>
                            </a>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../admin/all_enrollments.php" aria-expanded="false">
                                <i class="icon-note"></i>
                                <span class="hide-menu">Enrollments</span>
                            </a>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../admin/all_events.php" aria-expanded="false">
                                <i class="ti-calendar"></i>
                                <span class="hide-menu">Events</span>
                            </a>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../admin/operations.php" aria-expanded="false">
                                <i class="ti-layers-alt"></i>
                                <span class="hide-menu"><?=$operation_tab_title?></span>
                            </a>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../admin/reports.php" aria-expanded="false">
                                <i class="ti-bar-chart"></i>
                                <span class="hide-menu">Reports</span>
                            </a>
                        </li>
                        <!--<li>
                            <a class="waves-effect waves-dark" href="../admin/setup.php" aria-expanded="false">
                                <i class="ti-settings"></i>
                                <span class="hide-menu">Setup</span>
                            </a>
                        </li>-->
                    <?php } ?>

                    <?php if($_SESSION['PK_ROLES'] == 3) { ?>
                        <li> <a class="has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false"><i class="ti-layout-grid2"></i><span class="hide-menu">Setup</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="../super_admin/all_users.php">Users</a></li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if($_SESSION['PK_ROLES'] == 4) { ?>
                        <li>
                            <?php if ($view=='list') { ?>
                                <a class="waves-effect waves-dark" href="../customer/all_schedules.php?view=table" aria-expanded="false">
                                    <i class="icon-calender"></i>
                                    <span class="hide-menu">Calendar</span>
                                </a>
                            <?php } elseif ($view=='table') { ?>
                                <a class="waves-effect waves-dark" href="../customer/all_schedules.php?view=list" aria-expanded="false">
                                    <i class="icon-list"></i>
                                    <span class="hide-menu">List</span>
                                </a>
                            <?php } ?>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../customer/all_gift_certificates.php" aria-expanded="false">
                                <i class="icons-Gift-Box"></i>
                                <span class="hide-menu">Gift Cards</span>
                            </a>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../customer/billing.php" aria-expanded="false">
                                <i class="icons-Receipt-2"></i>
                                <span class="hide-menu">Billing</span>
                            </a>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../customer/accounts.php" aria-expanded="false">
                                <i class="icons-Receipt"></i>
                                <span class="hide-menu">Accounts</span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if($_SESSION['PK_ROLES'] == 5) { ?>
                        <li>
                            <?php if ($view=='list') { ?>
                                <a class="waves-effect waves-dark" href="../service_provider/all_schedules.php?view=table" aria-expanded="false">
                                    <i class="icon-calender"></i>
                                    <span class="hide-menu">Calendar</span>
                                </a>
                            <?php } elseif ($view=='table') { ?>
                                <a class="waves-effect waves-dark" href="../service_provider/all_schedules.php?view=list" aria-expanded="false">
                                    <i class="icon-list"></i>
                                    <span class="hide-menu">List</span>
                                </a>
                            <?php } ?>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../service_provider/operations.php" aria-expanded="false">
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
