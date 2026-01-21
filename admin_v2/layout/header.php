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
    .navbar-nav .nav-link {
        padding-right: 1.5rem !important;
        padding-left: 1.5rem !important;
        letter-spacing: 0.6px !important;
    }

    .navbar-nav .nav-link {
        position: relative;
    }

    .navbar-nav .nav-link.active {
        background-color: transparent !important;
        color: #fff !important;
        font-weight: 600 !important;
    }

    .navbar-nav .nav-link.active::after {
        content: "";
        position: absolute;
        left: 0;
        bottom: -15px;
        /* 👈 moved 20px down */
        width: 100%;
        height: 5px;
        background-color: #39b54a;
        border-radius: 2px;
    }

    .top-bar-icon {
        font-size: 21px;
        color: #fff;
    }
</style>
<header class="app-topbar">
    <div class="container-fluid topbar-menu">
        <div class="d-flex align-items-center gap-2">
            <div id="user-dropdown-detailed" class="topbar-item py-3 border-end me-3 pe-2 brd-light">
                <div class="dropdown">
                    <a class="topbar-link dropdown-toggle px-2" data-bs-toggle="dropdown" href="location" aria-haspopup="false" aria-expanded="false">
                        <img src="assets/images/logo.jpg" width="40" class="me-2 d-flex" alt="user-image" />
                        <div class="d-flex align-items-center gap-1">
                            <span>
                                <h6 class="my-0 f14 lh-1 pro-username text-white">Dance Studio</h6>
                                <span class="f12 lh-1">AMTO, AMWH</span>
                            </span>
                            <i class="ti ti-chevron-down align-middle"></i>
                        </div>
                    </a>
                    <!-- <div class="dropdown-menu dropdown-menu-end">
                        <a href="#!" class="dropdown-item">
                            <span class="align-middle">Profile</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <span class="align-middle">Notifications</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <span class="align-middle">Account Settings</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <span class="align-middle">Support Center</span>
                        </a>
                        <a href="auth-lock-screen.html" class="dropdown-item">
                            <span class="align-middle">Lock Screen</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item fw-semibold">
                            <span class="align-middle">Log Out</span>
                        </a>
                    </div> -->
                </div>
            </div>
            <nav class="navbar navbar-expand-lg navbar-dark py-0">
                <div class="topbar-item d-none d-sm-flex" style="margin-left: -20px; margin-right: 20px;">
                    <a class="top-bar-icon" href="#">
                        <i class="fa fa-search" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                    <div class="navbar-nav">
                        <a class="nav-link <?= (('calendar.php' === $current_address) ? 'active' : '') ?>" href="calendar.php">Calendar</a>
                        <a class="nav-link <?= (('email.php' === $current_address) ? 'active' : '') ?>" href="../email/email.php?type=inbox">Messages</a>
                        <a class="nav-link <?= (('all_customers.php' === $current_address) ? 'active' : '') ?>" href="all_customers.php">Customers</a>
                        <a class="nav-link <?= (('payment_due_report.php' === $current_address) ? 'active' : '') ?>" href="payment_due_report.php">Billing</a>
                        <a class="nav-link <?= (('all_leads.php' === $current_address) ? 'active' : '') ?>" href="all_leads.php">Leads</a>
                        <a class="nav-link" href="#">Marketing</a>
                        <a class="nav-link <?= (('all_events.php' === $current_address) ? 'active' : '') ?>" href="all_events.php">Events</a>
                        <a class="nav-link <?= (('all_products.php' === $current_address) ? 'active' : '') ?>" href="all_products.php">E-Commerce</a>
                        <a class="nav-link <?= (('reports.php' === $current_address) ? 'active' : '') ?>" href="reports.php">Reports</a>
                    </div>
                </div>
            </nav>
        </div>

        <div class="d-flex align-items-center" style="gap: 2rem !important;">

            <div class="topbar-item d-none d-sm-flex">
                <a class="top-bar-icon" href="to_do_list.php">
                    <i class="fa fa-tasks" aria-hidden="true"></i>
                </a>
            </div>

            <div class="topbar-item d-none d-sm-flex">
                <a class="top-bar-icon" href="#">
                    <i class="fa fa-bell" aria-hidden="true"></i>
                </a>
            </div>

            <div class="topbar-item d-none d-sm-flex">
                <a class="top-bar-icon" href="setup.php">
                    <i class="fa fa-cog" aria-hidden="true"></i>
                </a>
            </div>


            <div id="user-dropdown-detailed" class="topbar-item nav-user">
                <div class="dropdown">
                    <a class="topbar-link dropdown-toggle px-2" data-bs-toggle="dropdown" href="profile-menu" aria-haspopup="false" aria-expanded="false">
                        <img src="assets/images/profile.png" width="32" class="rounded-circle me-2 d-flex" alt="user-image" />
                        <div class="d-flex align-items-center gap-1">
                            <h6 class="my-0 f14 lh-1 pro-username text-white fw-normal"><?= $_SESSION["FIRST_NAME"] . " " . $_SESSION["LAST_NAME"] ?></h6>
                            <i class="ti ti-chevron-down align-middle"></i>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="../admin/my_profile.php" class="dropdown-item">
                            <span class="align-middle">Profile</span>
                        </a>
                        <!-- <a href="javascript:void(0);" class="dropdown-item">
                            <span class="align-middle">Notifications</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <span class="align-middle">Account Settings</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <span class="align-middle">Support Center</span>
                        </a>
                        <a href="auth-lock-screen.html" class="dropdown-item">
                            <span class="align-middle">Lock Screen</span>
                        </a> -->
                        <a href="../logout.php" class="dropdown-item fw-semibold">
                            <span class="align-middle">Log Out</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>