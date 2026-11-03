<?php
require_once('../global/config.php');
$title = "Setup";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>
<style>
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

    /*    #dropdown-products {
        display: none;
    }

    #menu-products:hover #dropdown-products {
        display: block;
    }*/
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <?php require_once('../includes/setup_menu.php') ?>
            <div class="container-fluid body_content m-0">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="row d-none">
                    <div class="col-12">
                        <div class="card">
                            <div class="row" style="padding: 15px 35px 35px 35px;">
                                <div class="col-md-3 col-sm-3 mt-3">
                                    <h4 class="card-title">General</h4>
                                    <div>
                                        <ul class="menu-list">
                                            <li><a href="business_profile.php">Business Profile</a></li>
                                            <li><a href="all_locations.php">Locations</a></li>
                                            <li><a href="all_users.php">Users</a></li>
                                            <!--<li><a href="all_service_providers.php"><?php /*=$service_provider_title*/ ?></a></li>-->
                                            <li><a href="all_services.php">Services</a></li>
                                            <li><a href="all_packages.php">Packages</a></li>
                                            <li><a href="all_document_library.php">Document Library</a></li>
                                            <li><a href="all_gift_certificates.php">Gift Certificate</a></li>
                                            <li><a href="all_gift_certificate_setup.php">Gift Certificate Setup</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-3 mt-3">
                                    <h4 class="card-title">Communications</h4>
                                    <div>
                                        <ul class="menu-list">
                                            <li><a href="all_email_accounts.php">Email Accounts</a></li>
                                            <li><a href="all_email_templates.php">Email Templates</a></li>
                                            <li><a href="all_text_templates.php">Text Templates</a></li>
                                            <li><a href="all_event_types.php">Event Types</a></li>
                                            <li><a href="all_inquiry_methods.php">Inquiry Method</a></li>
                                            <li><a href="all_scheduling_codes.php">Scheduling Codes</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
</body>

</html>