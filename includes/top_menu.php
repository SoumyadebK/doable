<style>
    #top_menu {
        z-index: 100;
    }
</style>

<header id="top_menu" class="topbar">
    <nav class="navbar top-navbar navbar-expand-md navbar-dark">
        <!-- ============================================================== -->
        <!-- Logo -->
        <!-- ============================================================== -->
        <div class="navbar-header" style="background-color: #000; width: 100px;">
            <?php if ($_SESSION["PK_ROLES"] == 1) { ?>
                <a class="navbar-brand" href="../super_admin/all_accounts.php">
                    <img src="../assets/images/doable_logo.png" alt="LOGO" style="height: 60px; width: auto;">
                </a>
            <?php } elseif ($_SESSION["PK_ROLES"] == 2) {
                if ($business_logo == "" || $business_logo == null) { ?>
                    <a class="navbar-brand" href="../admin/my_profile.php">
                        <b>DOABLE</b>
                    </a>
                <?php } else { ?>
                    <a class="navbar-brand" href="../admin/my_profile.php">
                        <img src="<?= $business_logo ?>" alt="LOGO" style="height: 60px; width: auto;">
                    </a>
                <?php } ?>
                <?php
            } elseif ($_SESSION["PK_ROLES"] == 4) {
                if ($business_logo == "" || $business_logo == null) { ?>
                    <a class="navbar-brand" href="../customer/my_profile.php">
                        <b>DOABLE</b>
                    </a>
                <?php } else { ?>
                    <a class="navbar-brand" href="../customer/my_profile.php">
                        <img src="<?= $business_logo ?>" alt="LOGO" style="height: 60px; width: auto;">
                    </a>
                <?php } ?>
                <?php
            } elseif ($_SESSION["PK_ROLES"] == 5) {
                if ($business_logo == "" || $business_logo == null) { ?>
                    <a class="navbar-brand" href="../service_provider/my_profile.php">
                        <b>DOABLE</b>
                    </a>
                <?php } else { ?>
                    <a class="navbar-brand" href="../service_provider/my_profile.php">
                        <img src="<?= $business_logo ?>" alt="LOGO" style="height: 60px; width: auto;">
                    </a>
                <?php } ?>
                <?php
            } ?>
        </div>
        <!-- ============================================================== -->
        <!-- End Logo -->
        <!-- ============================================================== -->
        <div class="navbar-collapse">
            <!-- ============================================================== -->
            <!-- toggle and nav items -->
            <!-- ============================================================== -->
            <ul class="navbar-nav me-auto">
                <!-- ============================================================== -->
                <!-- Search -->
                <!-- ============================================================== -->
                <li class="nav-item">
                    <?php if ($_SESSION["PK_ROLES"] == 1) { ?>
                        <p style="font-size: 23px; font-weight: 400; color: white; margin-left: 40px; margin-top: 14px;">Super Admin</p>


                    <?php } elseif (
                        $_SESSION["PK_ROLES"] == 2 ||
                        $_SESSION["PK_ROLES"] == 4 ||
                        $_SESSION["PK_ROLES"] == 5
                    ) { ?>
                        <p style="width: 450px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 23px; font-weight: 400; color: white; margin-left: 40px; margin-top: 14px;"><?= $business_name ?></p>
                    <?php } ?>
                </li>
            </ul>
            <!-- ============================================================== -->
            <!-- User profile and search -->
            <!-- ============================================================== -->
            <ul class="navbar-nav my-lg-0">
                <?php if ($_SESSION["PK_ROLES"] == 2 || $_SESSION["PK_ROLES"] == 3) { ?>
                    <li class="nav-item m-t-15">
                        <div id="location" class="multiselect-box" style="width: 300px">
                            <select class="multi_select_location" onchange="selectDefaultLocation(this);" multiple>
                                <?php
                                if ($_SESSION["PK_ROLES"] == 3) {
                                    $selected_location = [];
                                    $selected_location_row = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` WHERE `PK_USER` = ".$_SESSION['PK_USER']);
                                    while (!$selected_location_row->EOF) {
                                        echo $selected_location_row->fields['PK_LOCATION'];
                                        $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                                        $selected_location_row->MoveNext();
                                    }
                                    $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_LOCATION IN (".implode(',', $selected_location).")");
                                } else {
                                    $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                }
                                while (!$row->EOF) { ?>
                                    <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=(!empty($_SESSION['DEFAULT_LOCATION_ID']) && in_array($row->fields['PK_LOCATION'], explode(',', $_SESSION['DEFAULT_LOCATION_ID'])))?'selected':''?>><?=$row->fields['LOCATION_NAME']?></option>
                                <?php $row->MoveNext(); } ?>
                            </select>
                        </div>
                    </li>
                <?php } ?>


                <li class="nav-item" style="margin-top: 4px;">
                    <?php //if ($_SESSION["PK_ROLES"] == 1) { ?>
                    <!-- <a href="email.php" style="margin-left: 40px;color:white;">Email List</a> -->
                    <a class="nav-link dropdown-toggle waves-effect waves-dark" href="email.php" aria-haspopup="true" aria-expanded="false">
                        <img src="../assets/images/mail_icon.png" alt="Mail" style="height: 35px; width: 35px; background-color: white;">
                        <div class="notify"> <span class="heartbit"></span> <span class="point"></span> </div>
                    </a>
                    <?php //} ?>
                </li>

                <?php if($_SESSION['ACCESS_TOKEN'] && $_SESSION['TICKET_SYSTEM_ACCESS']==1):?>
                    <li class="nav-item" style="margin-top: 4px;">
                        <?php //if ($_SESSION["PK_ROLES"] == 1) { ?>
                        <!-- <a href="email.php" style="margin-left: 40px;color:white;">Email List</a> -->
                        <a class="nav-link dropdown-toggle waves-effect waves-dark" target="_blank" href="https://focusbiz.com/sso.php?t=<?=$_SESSION['ACCESS_TOKEN'] ?>" aria-haspopup="true" aria-expanded="false" title="Create Support Tickets">
                            <img src="../assets/images/icon/ticket.png" alt="Mail" style="height: 35px; width: 35px; background-color: white;">
                            <div class="notify"> <span class="heartbit"></span> <span class="point"></span> </div>
                        </a>
                        <?php //} ?>
                    </li>
                <?php endif;?>

                <li class="nav-item dropdown" style="margin-top: 4px;">
                    <a class="nav-link dropdown-toggle waves-effect waves-dark" href="javascript:" onclick="getCartItemList()" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img src="../assets/images/icon/cart.png" alt="Mail" style="height: 35px; width: 35px;">
                        <!--<div class="notify"> <span class="heartbit"></span> <span class="point"></span> </div>-->
                    </a>

                    <div id="cart_items" class="dropdown-menu dropdown-menu-end animated bounceInDown" style="margin-right: 15%; width: 400px;">
                        <div class="card">
                            <div class="card-header">
                                <div class="row" style="text-align: center;">
                                    <h5 style="font-weight: bold; color: #39b54a;">Cart Items</h5>
                                </div>
                            </div>
                            <div id="cart_item_list">

                            </div>

                    </div>
                </li>

                <li class="nav-item" style="margin-top: 4px;">
                    <a class="nav-link dropdown-toggle waves-effect waves-dark" href="../admin/manage_help.php" aria-haspopup="true" aria-expanded="false">
                        <img src="../assets/images/help.png" alt="Help" style="height: 35px; width: 35px;">
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle waves-effect waves-dark" href="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?php
/*                        $res = $db->Execute(
                            "SELECT USER_IMAGE FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'"
                        );
                        if (empty($res->fields["USER_IMAGE"])) { */?><!--
                            <img src="../assets/images/users/user_image_demo.jpg" alt="user-img" class="img-circle" width="35" height="35"> &nbsp;&nbsp;&nbsp;<?php /*= $_SESSION[
                        "FIRST_NAME"
                        ] .
                        " " .
                        $_SESSION[
                        "LAST_NAME"
                        ] */?> <i class="fas fa-angle-down"></i>
                        <?php /*} else { */?>
                            <img src="<?php /*= $res->fields[
                            "USER_IMAGE"
                            ] */?>" alt="user-img" class="img-circle" width="35" height="35"> &nbsp;&nbsp;&nbsp;<?php /*= $_SESSION["FIRST_NAME"]." ".$_SESSION["LAST_NAME"] */?> <i class="fas fa-angle-down"></i>
                        --><?php /*}
                        */?>
                        <?= $_SESSION["FIRST_NAME"]." ".$_SESSION["LAST_NAME"] ?> <i class="fas fa-angle-down"></i>
                    </a>
                    <div id="logout" class="dropdown-menu dropdown-menu-end mailbox animated bounceInDown" style="margin-right: 45%">
                        <ul>
                            <?php if ($_SESSION["PK_ROLES"] == 1) { ?>
                                <li>
                                    <a href="../super_admin/my_profile.php" class="dropdown-item"><i class="ti-user"></i> My Profile</a>
                                </li>
                            <?php } elseif ($_SESSION["PK_ROLES"] == 2) { ?>
                                <li>
                                    <a href="../admin/my_profile.php" class="dropdown-item"><i class="ti-user"></i> My Profile</a>
                                </li>
                            <?php } elseif ($_SESSION["PK_ROLES"] == 4) { ?>
                                <li>
                                    <a href="../customer/my_profile.php" class="dropdown-item"><i class="ti-user"></i> My Profile</a>
                                </li>
                            <?php } elseif ($_SESSION["PK_ROLES"] == 5) { ?>
                                <li>
                                    <a href="../service_provider/my_profile.php" class="dropdown-item"><i class="ti-user"></i> My Profile</a>
                                </li>
                            <?php } ?>
                            <li>
                                <a href="../logout.php" class="dropdown-item"><i class="fas fa-power-off"></i> Logout</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item" style="margin-top: 4px;">
                    <?php if ($_SESSION["PK_ROLES"] != 1) { ?>
                        <a class="navbar-brand" href="javascript:">
                            <img src="../assets/images/doable_logo.png" alt="LOGO" style="height: 60px; width: auto;">
                        </a>
                    <?php } ?>
                </li>
            </ul>
        </div>
    </nav>
</header>
