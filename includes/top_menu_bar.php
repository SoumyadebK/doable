<div class="container-fluid p-0 nav-top-new">
    <div class="row">
        <div class="d-flex flex-column flex-md-row align-items-center py-2 px-4 bg-white border-bottomm box-shadow justify-content-end">
            <nav class="my-2 my-md-0 mr-md-3 new-top-nav">
                <ul id="sidebarnav">
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
                        <li>
                            <a class="waves-effect waves-dark" href="../admin/all_schedules.php" aria-expanded="false">
                                <i class="icon-calender"></i>
                                <span class="hide-menu">Appointments</span>
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
                                <span class="hide-menu">Enrollment</span>
                            </a>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../admin/all_events.php" aria-expanded="false">
                                <i class="ti-calendar"></i>
                                <span class="hide-menu">Events</span>
                            </a>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../admin/reports.php" aria-expanded="false">
                                <i class="ti-bar-chart"></i>
                                <span class="hide-menu">Reports</span>
                            </a>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../admin/setup.php" aria-expanded="false">
                                <i class="ti-settings"></i>
                                <span class="hide-menu">Setup</span>
                            </a>
                        </li>
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
                            <a class="waves-effect waves-dark" href="../customer/all_schedules.php" aria-expanded="false">
                                <i class="icon-calender"></i>
                                <span class="hide-menu">Appointments</span>
                            </a>
                        </li>
                        <li>
                            <a class="waves-effect waves-dark" href="../customer/billing.php" aria-expanded="false">
                                <i class="icons-Receipt"></i>
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
                            <a class="waves-effect waves-dark" href="../service_provider/all_schedules.php" aria-expanded="false">
                                <i class="icon-calender"></i>
                                <span class="hide-menu">Appointments</span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </nav>
        </div>
    </div>
</div>
