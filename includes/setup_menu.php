<?php
$mail_url = parse_url($_SERVER['REQUEST_URI']);
$url_array = explode("/", $mail_url['path']);
if($_SERVER['HTTP_HOST'] == 'localhost' ) {
    $current_address = $url_array[3];
} else {
    $current_address = $url_array[2];
}
?>

<div class="container-fluid body_content p-0" style="margin-top: 67px;">
    <div class="row">
        <div class="col-12 new-top-menu">
            <nav class="navbar navbar-expand-lg navbar-light bg-light px-2 py-1 d-non">
                <div class="collapse col-6 navbar-collapse" id="navbarNavDropdown">
                    <ul class="navbar-nav">
                        <li id="menu-products" class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="javascript:" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                General
                            </a>
                            <div id="dropdown-products" class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <!-- <a class="dropdown-item" href="../admin/business_profile.php">Business Profile</a> -->
                                <!-- <a class="dropdown-item" href="../admin/settings.php">Settings</a> -->
                                <a class="dropdown-item" href="../admin/all_corporations.php">Corporations</a>
                                <a class="dropdown-item" href="../admin/all_locations.php">Locations</a>
                                <a class="dropdown-item" href="../admin/all_users.php">Users / Employees / Service Providers</a>
                                <a class="dropdown-item" href="../admin/deleted_customer.php">Deleted Customer</a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="javascript:" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Services
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <!--<a class="dropdown-item" href="../admin/all_services.php">Services</a>-->
                                <a class="dropdown-item" href="../admin/all_scheduling_codes.php">Scheduling Codes</a>
                                <a class="dropdown-item" href="../admin/all_service_codes.php">Services</a>
                                <a class="dropdown-item" href="../admin/all_packages.php">Packages</a>
                                <a class="dropdown-item" href="../admin/all_document_library.php">Document Library</a>
                                <!--<a class="dropdown-item" href="../admin/all_interests.php">Interests</a>
                                <a class="dropdown-item" href="../admin/all_skill_levels.php">Skill Levels</a>-->
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="javascript:" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Others
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <a class="dropdown-item" href="../admin/all_gift_certificates.php">Gift Certificate</a>
                                <a class="dropdown-item" href="../admin/all_gift_certificate_setup.php">Gift Certificate Setup</a>
                                <a class="dropdown-item" href="../admin/all_event_types.php">Event Types</a>
                                <a class="dropdown-item" href="../admin/all_inquiry_methods.php">Inquiry Method</a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="javascript:" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Communication
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <a class="dropdown-item" href="../admin/all_email_accounts.php">Email Accounts</a>
                                <a class="dropdown-item" href="../admin/all_email_templates.php">Email Templates</a>
                                <a class="dropdown-item" href="../admin/all_text_templates.php">Text Templates</a>
                                <a class="dropdown-item" href="../admin/test_chat_gpt.php">Assistant</a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="javascript:" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Operations
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <a class="dropdown-item" href="../admin/data_uploader.php">Data Uploader</a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="javascript:" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                eCommerce
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <a class="dropdown-item" href="../admin/all_products.php">Products</a>
                                <a class="dropdown-item" href="../admin/all_orders.php">Orders</a>
                            </div>
                        </li>
                    </ul>
                    <div style="margin-left: 15%;">
                        <?php
                        $currentURL = parse_url($_SERVER['REQUEST_URI']);
                        $url = explode("/", $currentURL['path']);
                        if($_SERVER['HTTP_HOST'] == 'localhost' ) {
                            $address = $url[3];
                        } else {
                            $address = $url[2];
                        }
                        if ($address == "business_profile.php" || $address == "settings.php" || $address == "all_corporations.php" || $address == "all_locations.php" || $address == "all_users.php" || $address == "deleted_customer.php") { ?>
                            <ul class="nav nav-pills justify-content-center">
                                <!-- <li class="nav-item"><a class="nav-link <?=($address == 'business_profile.php') ? 'active' : ''?>" href="../admin/business_profile.php">Business Profile</a></li> -->
                                <!-- <li class="nav-item"><a class="nav-link <?=($address == 'settings.php') ? 'active' : ''?>" href="../admin/settings.php">Settings</a></li> -->
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_corporations.php') ? 'active' : ''?>" href="../admin/all_corporations.php">Corporations</a></li>
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_locations.php') ? 'active' : ''?>" href="../admin/all_locations.php">Locations</a></li>
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_users.php') ? 'active' : ''?>" href="../admin/all_users.php">Users</a></li>
                                <li class="nav-item"><a class="nav-link <?=($address == 'deleted_customer.php') ? 'active' : ''?>" href="../admin/deleted_customer.php">Deleted Customer</a></li>
                            </ul>
                        <?php } elseif ($address == "all_service_codes.php" || $address == "all_packages.php" || $address == "all_scheduling_codes.php" || $address == "all_document_library.php" || $address == "all_interests.php" || $address == "all_skill_levels.php") { ?>
                            <ul class="nav nav-pills justify-content-center">
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_scheduling_codes.php') ? 'active' : ''?>" href="../admin/all_scheduling_codes.php">Scheduling Codes</a></li>
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_service_codes.php') ? 'active' : ''?>" href="../admin/all_service_codes.php">Services</a></li>
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_packages.php') ? 'active' : ''?>" href="../admin/all_packages.php">Packages</a></li>
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_document_library.php') ? 'active' : ''?>" href="../admin/all_document_library.php">Document Library</a></li>
                                <!--<li class="nav-item"><a class="nav-link <?=($address == 'all_interests.php') ? 'active' : ''?>" href="../admin/all_interests.php">Interests</a></li>
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_skill_levels.php') ? 'active' : ''?>" href="../admin/all_skill_levels.php">Skill Levels</a></li>-->
                            </ul>
                        <?php } elseif ($address == "all_gift_certificates.php" || $address == "all_gift_certificate_setup.php" || $address == "all_event_types.php" || $address == "all_inquiry_methods.php") { ?>
                            <ul class="nav nav-pills justify-content-center">
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_gift_certificates.php') ? 'active' : ''?>" href="../admin/all_gift_certificates.php">Gift Certificate</a></li>
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_gift_certificate_setup.php') ? 'active' : ''?>" href="../admin/all_gift_certificate_setup.php">Gift Certificate Setup</a></li>
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_event_types.php') ? 'active' : ''?>" href="../admin/all_event_types.php">Event Types</a></li>
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_inquiry_methods.php') ? 'active' : ''?>" href="../admin/all_inquiry_methods.php">Inquiry Method</a></li>
                            </ul>
                        <?php } elseif ($address == "all_email_accounts.php" || $address == "all_email_templates.php" || $address == "all_text_templates.php" || $address == "test_chat_gpt.php") { ?>
                            <ul class="nav nav-pills justify-content-center">
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_email_accounts.php') ? 'active' : ''?>" href="../admin/all_email_accounts.php">Email Accounts</a></li>
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_email_templates.php') ? 'active' : ''?>" href="../admin/all_email_templates.php">Email Templates</a></li>
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_text_templates.php') ? 'active' : ''?>" href="../admin/all_text_templates.php">Text Templates</a></li>
                                <li class="nav-item"><a class="nav-link <?=($address == 'test_chat_gpt.php') ? 'active' : ''?>" href="../admin/test_chat_gpt.php">Assistant</a></li>
                            </ul>
                        <?php } elseif ($address == "data_uploader.php") { ?>
                            <ul class="nav nav-pills justify-content-center">
                                <li class="nav-item"><a class="nav-link <?=($address == 'data_uploader.php') ? 'active' : ''?>" href="../admin/data_uploader.php">Data Uploader</a></li>
                            </ul>
                        <?php } elseif ($address == "all_products.php" || $address == "all_orders.php" || $address == "order_details.php") { ?>
                            <ul class="nav nav-pills justify-content-center">
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_products.php') ? 'active' : ''?>" href="../admin/all_products.php">Products</a></li>
                                <li class="nav-item"><a class="nav-link <?=($address == 'all_orders.php') ? 'active' : ''?>" href="../admin/all_orders.php">Orders</a></li>
                            </ul>
                        <?php } ?>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</div>

<style>
    .nav-tabs li {
        display: inline-block; /* Display list items as inline-block */
        background-color: whitesmoke;
    }
</style>

