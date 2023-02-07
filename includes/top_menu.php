<header class="topbar">
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
                        <p style="font-size: 23px; font-weight: 400; color: white; margin-left: 40px; margin-top: 14px;"><?= $business_name ?></p>
                    <?php } ?>
                </li>
            </ul>
            <!-- ============================================================== -->
            <!-- User profile and search -->
            <!-- ============================================================== -->
            <ul class="navbar-nav my-lg-0">
                <?php if ($_SESSION["PK_ROLES"] == 2) { ?>
                    <li class="nav-item m-r-40 m-t-15">
                        <select class="form-control" onchange="selectDefaultLocation(this);">
                            <option value="0">Select Location</option>
                            <?php
                            $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=(!empty($_SESSION['DEFAULT_LOCATION_ID']) && $_SESSION['DEFAULT_LOCATION_ID'] == $row->fields['PK_LOCATION'])?'selected':''?>><?=$row->fields['LOCATION_NAME']?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
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

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle waves-effect waves-dark" href="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?php
                        $res = $db->Execute(
                            "SELECT USER_IMAGE FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'"
                        );
                        if (empty($res->fields["USER_IMAGE"])) { ?>
                            <img src="../assets/images/users/user_image_demo.jpg" alt="user-img" class="img-circle" width="35" height="35"> &nbsp;&nbsp;&nbsp;<?= $_SESSION[
                        "FIRST_NAME"
                        ] .
                        " " .
                        $_SESSION[
                        "LAST_NAME"
                        ] ?> <i class="fas fa-angle-down"></i>
                        <?php } else { ?>
                            <img src="<?= $res->fields[
                            "USER_IMAGE"
                            ] ?>" alt="user-img" class="img-circle" width="35" height="35"> &nbsp;&nbsp;&nbsp;<?= $_SESSION[
                        "FIRST_NAME"
                        ] .
                        " " .
                        $_SESSION["LAST_NAME"] ?> <i class="fas fa-angle-down"></i>
                        <?php }
                        ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end mailbox animated bounceInDown">
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
            </ul>
        </div>
    </nav>
</header>
