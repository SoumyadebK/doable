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
        bottom: -8px;
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

    .multi-select-dropdown {
        min-width: 280px;
        background-color: #ffffff;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        z-index: 1000;
    }

    .multi-select-header {
        padding: 12px 16px;
        border-bottom: 1px solid #e9ecef;
        font-size: 14px;
        font-weight: 500;
        color: #333;
    }

    .multi-select-options {
        max-height: 250px;
        overflow-y: auto;
        padding: 8px 0;
    }

    .multi-select-option {
        padding: 10px 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: background-color 0.2s ease;
    }

    .multi-select-option:hover {
        background-color: #f8f9fa;
    }

    .multi-select-option input[type="checkbox"] {
        cursor: pointer;
    }

    .multi-select-option label {
        cursor: pointer;
        margin: 0;
        flex: 1;
        font-size: 14px;
        color: #333;
    }

    .multi-select-footer {
        padding: 12px 16px;
        border-top: 1px solid #e9ecef;
        display: flex;
        gap: 8px;
    }

    .multi-select-footer button {
        flex: 1;
        padding: 8px 12px;
        font-size: 13px;
        border: 1px solid #e9ecef;
        background-color: #ffffff;
        color: #333;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .multi-select-footer button:hover {
        background-color: #f8f9fa;
    }

    .multi-select-footer button.apply-btn {
        background-color: #39b54a;
        color: #ffffff;
        border-color: #39b54a;
    }

    .multi-select-footer button.apply-btn:hover {
        background-color: #2fa03b;
    }

    .location-display-name {
        min-width: 150px;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: inline-block;
        text-align: center;
    }

    .page-wrapper {
        padding-top: 0px !important;
    }

    .container-fluid {
        margin-top: 0px !important;
    }

    .xxx ul.nav li.dropdown:hover ul.dropdown-menu {
        display: block;
    }

    .sub-menu {
        padding: 10px 10px;
        font-size: 14px;
    }

    .sub-menu a {
        padding: 5px;
        display: block;
        border-radius: 5px;
    }

    .sub-menu a:hover {
        color: #333;
        background-color: #ddd;
    }

    .text-success {
        color: #39b54a !important;
    }

    #cart_items {
        width: 400px;
        right: 180px !important;
        top: 55px !important;
        left: auto !important;
    }

    .button {
        color: white;
        display: inline-block;
        position: relative;
        padding: 2px 5px;
    }

    .button__badge {
        background-color: #fa3e3e;
        border-radius: 20px;
        color: white;
        padding: 1px 4px;
        font-size: 10px;
        position: absolute;
        top: 4px;
        right: -8px;
        line-height: normal;
    }
</style>
<header class="app-topbar">
    <div class="container-fluid topbar-menu">
        <div class="d-flex align-items-center gap-2">
            <div id="user-dropdown-detailed" class="topbar-item py-3 border-end me-3 pe-2 brd-light">
                <div class="dropdown">



                    <?php
                    $selected_location = [];
                    if ($_SESSION["PK_ROLES"] == 2 || $_SESSION["PK_ROLES"] == 11) {
                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME, LOCATION_CODE FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                    } elseif ($_SESSION["PK_ROLES"] == 4) {
                        $selected_location_row = $db->Execute("SELECT `PRIMARY_LOCATION_ID` FROM `DOA_USER_MASTER` WHERE `PK_USER` = " . $_SESSION['PK_USER']);
                        while (!$selected_location_row->EOF) {
                            $selected_location[] = $selected_location_row->fields['PRIMARY_LOCATION_ID'];
                            $selected_location_row->MoveNext();
                        }
                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME, LOCATION_CODE FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_LOCATION IN (" . implode(',', $selected_location) . ")");
                    } else {
                        $selected_location_row = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` WHERE `PK_USER` = " . $_SESSION['PK_USER']);
                        while (!$selected_location_row->EOF) {
                            //echo $selected_location_row->fields['PK_LOCATION'];
                            $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                            $selected_location_row->MoveNext();
                        }
                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME, LOCATION_CODE FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_LOCATION IN (" . implode(',', $selected_location) . ")");
                    }
                    $selected_location_names = [];
                    $DEFAULT_LOCATION_ARRAY = explode(',', $_SESSION['DEFAULT_LOCATION_ID']);
                    foreach ($DEFAULT_LOCATION_ARRAY as $loc_id) {
                        $loc_row = $db->Execute("SELECT LOCATION_CODE FROM DOA_LOCATION WHERE PK_LOCATION = " . $loc_id);
                        if (!$loc_row->EOF) {
                            $selected_location_names[] = $loc_row->fields['LOCATION_CODE'];
                        }
                    }
                    ?>

                    <a class="topbar-link px-2" data-bs-toggle="dropdown" href="location" aria-haspopup="false" aria-expanded="false">
                        <!-- <img src="assets/images/logo.jpg" width="40" class="me-2 d-flex" alt="user-image" /> -->
                        <div class="d-flex align-items-center gap-1">
                            <span>
                                <h6 class="my-0 f14 lh-1 pro-username text-white location-display-name"><?= implode(', ', $selected_location_names) ?> <i class="fa fa-angle-down" style="font-size: 20px; margin: 0px 3px 0px 10px;"></i></h6>
                            </span>
                        </div>
                    </a>

                    <!-- Multi-Select Dropdown -->
                    <div class="dropdown-menu dropdown-menu-end multi-select-dropdown" id="locationMultiSelect">
                        <div class="multi-select-header">Select Locations</div>
                        <div class="multi-select-options">

                            <?php
                            if (($_SESSION["PK_ROLES"] == 2 || $_SESSION["PK_ROLES"] == 11) || count($selected_location) > 1) { ?>
                                <?php
                                while (!$row->EOF) { ?>
                                    <div class="multi-select-option">
                                        <input type="checkbox" id="<?= $row->fields['PK_LOCATION'] ?>" value="<?= $row->fields['PK_LOCATION'] ?>" class="location-checkbox" <?= (!empty($_SESSION['DEFAULT_LOCATION_ID']) && in_array($row->fields['PK_LOCATION'], explode(',', $_SESSION['DEFAULT_LOCATION_ID']))) ? 'checked' : '' ?>>
                                        <label for="<?= $row->fields['PK_LOCATION'] ?>"><?= $row->fields['LOCATION_NAME'] ?> (<?= $row->fields['LOCATION_CODE'] ?>)</label>
                                    </div>
                                <?php $row->MoveNext();
                                } ?>
                            <?php } else {
                                $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_LOCATION IN (" . implode(',', $selected_location) . ")"); ?>
                                <h4 style="color: white;"><?= $row->fields['LOCATION_NAME'] ?></h4>
                            <?php } ?>
                        </div>
                        <div class="multi-select-footer">
                            <button class="clear-btn" onclick="clearLocations()">Clear All</button>
                            <button class="apply-btn" onclick="selectViewingLocation()">Apply</button>
                        </div>
                    </div>

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
                <div class="topbar-item d-none d-sm-flex" style="margin-right: 18px; margin-bottom: 3px;">
                    <a class="top-bar-icon" href="#">
                        <i class="fa fa-search" aria-hidden="true" style="font-size: 18px;"></i>
                    </a>
                </div>
                <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                    <div class="nav-links-elements xxx">
                        <ul class="nav navbar-nav">
                            <li class="">
                                <a class="nav-link <?= (('calendar.php' === $current_address || 'calendar_list_view.php' === $current_address) ? 'active' : '') ?>" href="calendar.php">Calendar</a>
                            </li>
                            <li class="">
                                <a class="nav-link <?= (('email.php' === $current_address) ? 'active' : '') ?>" href="../email/email.php?type=inbox">Messages</a>
                            </li>
                            <li class="dropdown drop-menu">
                                <a href="javascript:;" class="nav-link <?= (('all_customers.php' === $current_address || 'all_enrollments.php' === $current_address) ? 'active' : '') ?>" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Customers <i class="fa fa-angle-down" style="font-size: 15px; margin: 0px 0px 0px 6px;"></i></a>
                                <ul class="dropdown-menu sub-menu">
                                    <li><a href="all_customers.php"><i class="bi bi-people" aria-hidden="true"></i> Customers</a></li>
                                    <li><a href="all_enrollments.php"><i class="bi bi-journal-text" aria-hidden="true"></i> Enrollments</a></li>
                                </ul>
                            </li>
                            <li class="">
                                <a class="nav-link <?= (('payment_due_report.php' === $current_address) ? 'active' : '') ?>" href="payment_due_report.php">Billing</a>
                            </li>
                            <li class="">
                                <a class="nav-link <?= (('leads_grid.php' === $current_address || 'leads_list.php' === $current_address) ? 'active' : '') ?>" href="leads_grid.php">Leads</a>
                            </li>
                            <li class="">
                                <a class="nav-link <?= (('all_events.php' === $current_address || 'events_list.php' === $current_address) ? 'active' : '') ?>" href="events_list.php">Events</a>
                            </li>
                            <li class="dropdown drop-menu">
                                <a href="javascript:;" class="nav-link <?= (('products_list.php' === $current_address || 'order_list.php' === $current_address) ? 'active' : '') ?>" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">E-Commerce <i class="fa fa-angle-down" style="font-size: 15px; margin: 0px 0px 0px 6px;"></i></a>
                                <ul class="dropdown-menu sub-menu">
                                    <li><a href="products_list.php"><i class="bi bi-cart" aria-hidden="true"></i> Products</a></li>
                                    <li><a href="orders_list.php"><i class="bi bi-list" aria-hidden="true"></i> Orders</a></li>
                                </ul>
                            </li>
                            <!-- Original Reports tab (line ~117) -->
                            <li class="">
                                <a class="nav-link <?= ((strpos($current_address, 'report') !== false) ? 'active' : '') ?>" href="reports.php">Reports</a>
                            </li>
                        </ul>
                    </div>
                    <!-- <div class="navbar-nav">
                        <a class="nav-link <?= (('calendar.php' === $current_address || 'calendar_list_view.php' === $current_address) ? 'active' : '') ?>" href="calendar.php">Calendar</a>
                        <a class="nav-link <?= (('email.php' === $current_address) ? 'active' : '') ?>" href="../email/email.php?type=inbox">Messages</a>
                        <a class="nav-link <?= (('all_customers.php' === $current_address) ? 'active' : '') ?>" href="all_customers.php">Customers</a>
                        <a class="nav-link <?= (('all_enrollments.php' === $current_address) ? 'active' : '') ?>" href="all_enrollments.php">Enrollments</a>
                        <a class="nav-link <?= (('payment_due_report.php' === $current_address) ? 'active' : '') ?>" href="payment_due_report.php">Billing</a>
                        <a class="nav-link <?= (('all_leads.php' === $current_address) ? 'active' : '') ?>" href="all_leads.php">Leads</a>
                        <a class="nav-link" href="#">Marketing</a>
                        <a class="nav-link <?= (('all_events.php' === $current_address) ? 'active' : '') ?>" href="all_events.php">Events</a>
                        <a class="nav-link <?= (('all_products.php' === $current_address) ? 'active' : '') ?>" href="all_products.php">E-Commerce</a>
                        <a class="nav-link <?= (('reports.php' === $current_address) ? 'active' : '') ?>" href="reports.php">Reports</a>
                    </div> -->
                </div>
            </nav>
        </div>

        <div class="d-flex align-items-center" style="gap: 1.5rem !important;">
            <?php if ($_SESSION["PK_ROLES"] == 2 || $_SESSION["PK_ROLES"] == 4 || $_SESSION["PK_ROLES"] == 11) { ?>
                <div class="topbar-item d-none d-sm-flex">
                    <a class="top-bar-icon" href="javascript:" onclick="getCartItemList()" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <!--<div class="notify" id="cart_notify" style="display: <?php /*=(isset($_SESSION['CART_DATA']) && count($_SESSION['CART_DATA']) > 0)?'':'none'*/ ?>;"> <span class="button"></span> </div>-->
                        <div class="button">
                            <i class="fa fa-shopping-cart" aria-hidden="true" style="font-size: 18px"></i>
                            <span class="button__badge" id="cart_count"><?= (isset($_SESSION['CART_DATA']) && count($_SESSION['CART_DATA']) > 0) ? count($_SESSION['CART_DATA']) : 0 ?></span>
                        </div>
                    </a>

                    <div id="cart_items"
                        class="dropdown-menu dropdown-menu-end animated bounceInDown p-0"
                        style="width: 400px; max-width: 95vw;">

                        <div class="card border-0">
                            <div class="card-header text-center">
                                <h5 class="fw-bold text-success mb-0">Cart Items</h5>
                            </div>

                            <div id="cart_item_list" class="card-body">
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <div class="topbar-item d-none d-sm-flex">
                <a class="top-bar-icon" href="to_do_list.php">
                    <i class="fa fa-tasks" aria-hidden="true" style="font-size: 18px;"></i>
                </a>
            </div>

            <div class="topbar-item d-none d-sm-flex">
                <a class="top-bar-icon" href="#">
                    <i class="fa fa-bell" aria-hidden="true" style="font-size: 18px;"></i>
                </a>
            </div>

            <div class="topbar-item d-none d-sm-flex">
                <a class="top-bar-icon" href="setup.php">
                    <i class="fa fa-cog" aria-hidden="true" style="font-size: 18px;"></i>
                </a>
            </div>


            <div id="user-dropdown-detailed" class="topbar-item nav-user">
                <div class="dropdown">
                    <a class="topbar-link px-2" data-bs-toggle="dropdown" href="profile-menu" aria-haspopup="false" aria-expanded="false" style="margin-top: 11px;">
                        <img src="assets/images/profile.png" width="32" class="rounded-circle me-2 d-flex" alt="user-image" />
                        <div class="d-flex align-items-center gap-1">
                            <h6 class="my-0 f14 lh-1 pro-username text-white fw-normal"><?= $_SESSION["FIRST_NAME"] . " " . $_SESSION["LAST_NAME"] ?> <i class="fa fa-angle-down" style="font-size: 15px; margin: 0px -10px 0px 8px;"></i></h6>
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