<?php
$mail_url = parse_url($_SERVER['REQUEST_URI']);
$url_array = explode("/", $mail_url['path']);
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $current_address = $url_array[3];
} else {
    $current_address = $url_array[2];
}
?>
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
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                    <div class="navbar-nav">
                        <a class="nav-link <?= (('calendar.php' === $current_address) ? 'active' : '') ?>" href="calendar.php">Calendar</a>
                        <a class="nav-link <?= (('appointment_list.php' === $current_address) ? 'active' : '') ?>" href="appointment_list.php">Appointments</a>
                        <a class="nav-link <?= (('to_do_list.php' === $current_address) ? 'active' : '') ?>" href="to_do_list.php">To-Do</a>
                        <a class="nav-link <?= (('all_leads.php' === $current_address) ? 'active' : '') ?>" href="all_leads.php">Leads</a>
                        <a class="nav-link <?= (('all_customers.php' === $current_address) ? 'active' : '') ?>" href="all_customers.php">Customers</a>
                        <a class="nav-link <?= (('all_enrollments.php' === $current_address) ? 'active' : '') ?>" href="all_enrollments.php">Enrollments</a>
                        <a class="nav-link <?= (('all_events.php' === $current_address) ? 'active' : '') ?>" href="all_events.php">Events</a>
                        <a class="nav-link <?= (('operations.php' === $current_address) ? 'active' : '') ?>" href="operations.php">Operations</a>
                        <a class="nav-link <?= (('reports.php' === $current_address) ? 'active' : '') ?>" href="reports.php">Reports</a>
                        <a class="nav-link <?= (('payment_due_report.php' === $current_address) ? 'active' : '') ?>" href="payment_due_report.php">Payment Due</a>
                    </div>
                </div>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2">
            <div class="topbar-item d-none d-sm-flex">
                <button class="topbar-link btn-theme-search" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M6.75 0C10.476 0 13.5 3.024 13.5 6.75C13.5 10.476 10.476 13.5 6.75 13.5C3.024 13.5 0 10.476 0 6.75C0 3.024 3.024 0 6.75 0ZM6.75 12C9.65025 12 12 9.65025 12 6.75C12 3.849 9.65025 1.5 6.75 1.5C3.849 1.5 1.5 3.849 1.5 6.75C1.5 9.65025 3.849 12 6.75 12ZM13.1137 12.0532L15.2355 14.1742L14.1742 15.2355L12.0532 13.1137L13.1137 12.0532Z" fill="#99A0AE" />
                    </svg>
                </button>
            </div>

            <div id="notification-dropdown-people" class="topbar-item">
                <div class="dropdown">
                    <button class="topbar-link dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" type="button" data-bs-auto-close="outside" aria-haspopup="false" aria-expanded="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="16" viewBox="0 0 15 16" fill="none">
                            <path d="M13.5 11.25H15V12.75H0V11.25H1.5V6C1.5 4.4087 2.13214 2.88258 3.25736 1.75736C4.38258 0.632141 5.9087 0 7.5 0C9.0913 0 10.6174 0.632141 11.7426 1.75736C12.8679 2.88258 13.5 4.4087 13.5 6V11.25ZM12 11.25V6C12 4.80653 11.5259 3.66193 10.682 2.81802C9.83807 1.97411 8.69347 1.5 7.5 1.5C6.30653 1.5 5.16193 1.97411 4.31802 2.81802C3.47411 3.66193 3 4.80653 3 6V11.25H12ZM5.25 14.25H9.75V15.75H5.25V14.25Z" fill="#99A0AE" />
                        </svg>
                        <span class="badge text-bg-danger badge-circle topbar-badge"></span>
                    </button>
                    <!-- End dropdown-menu -->
                </div>
                <!-- end dropdown-->
            </div>

            <div class="topbar-item d-none d-sm-flex">
                <button class="topbar-link btn-theme-setting" type="button" onclick="window.location.href = 'setup.php';">
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 17 17" fill="none">
                        <path d="M5.68934 2.17484L7.64459 0.21959C7.78524 0.0789866 7.97597 0 8.17484 0C8.37371 0 8.56444 0.0789866 8.70509 0.21959L10.6603 2.17484H13.4248C13.6238 2.17484 13.8145 2.25386 13.9552 2.39451C14.0958 2.53516 14.1748 2.72593 14.1748 2.92484V5.68934L16.1301 7.64459C16.2707 7.78524 16.3497 7.97597 16.3497 8.17484C16.3497 8.37371 16.2707 8.56444 16.1301 8.70509L14.1748 10.6603V13.4248C14.1748 13.6238 14.0958 13.8145 13.9552 13.9552C13.8145 14.0958 13.6238 14.1748 13.4248 14.1748H10.6603L8.70509 16.1301C8.56444 16.2707 8.37371 16.3497 8.17484 16.3497C7.97597 16.3497 7.78524 16.2707 7.64459 16.1301L5.68934 14.1748H2.92484C2.72593 14.1748 2.53516 14.0958 2.39451 13.9552C2.25386 13.8145 2.17484 13.6238 2.17484 13.4248V10.6603L0.21959 8.70509C0.0789866 8.56444 0 8.37371 0 8.17484C0 7.97597 0.0789866 7.78524 0.21959 7.64459L2.17484 5.68934V2.92484C2.17484 2.72593 2.25386 2.53516 2.39451 2.39451C2.53516 2.25386 2.72593 2.17484 2.92484 2.17484H5.68934ZM3.67484 3.67484V6.31109L1.81109 8.17484L3.67484 10.0386V12.6748H6.31109L8.17484 14.5386L10.0386 12.6748H12.6748V10.0386L14.5386 8.17484L12.6748 6.31109V3.67484H10.0386L8.17484 1.81109L6.31109 3.67484H3.67484ZM8.17484 11.1748C7.37919 11.1748 6.61613 10.8588 6.05352 10.2962C5.49091 9.73355 5.17484 8.97049 5.17484 8.17484C5.17484 7.37919 5.49091 6.61613 6.05352 6.05352C6.61613 5.49091 7.37919 5.17484 8.17484 5.17484C8.97049 5.17484 9.73355 5.49091 10.2962 6.05352C10.8588 6.61613 11.1748 7.37919 11.1748 8.17484C11.1748 8.97049 10.8588 9.73355 10.2962 10.2962C9.73355 10.8588 8.97049 11.1748 8.17484 11.1748ZM8.17484 9.67484C8.57266 9.67484 8.95419 9.5168 9.2355 9.2355C9.5168 8.95419 9.67484 8.57266 9.67484 8.17484C9.67484 7.77701 9.5168 7.39548 9.2355 7.11418C8.95419 6.83287 8.57266 6.67484 8.17484 6.67484C7.77701 6.67484 7.39548 6.83287 7.11418 7.11418C6.83287 7.39548 6.67484 7.77701 6.67484 8.17484C6.67484 8.57266 6.83287 8.95419 7.11418 9.2355C7.39548 9.5168 7.77701 9.67484 8.17484 9.67484Z" fill="#99A0AE" />
                    </svg>
                </button>
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