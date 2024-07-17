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
                                <a class="dropdown-item" href="../super_admin/all_rate_types.php">Rate Types</a>
                                <a class="dropdown-item" href="../super_admin/all_users.php">Users</a>
                                <a class="dropdown-item" href="../super_admin/all_departments.php">Departments</a>
                                <a class="dropdown-item" href="../super_admin/all_roles.php">Roles</a>
                                <a class="dropdown-item" href="../super_admin/all_permissions.php">Permission</a>
                                <a class="dropdown-item" href="../super_admin/all_countries.php">Countries</a>
                                <a class="dropdown-item" href="../super_admin/all_states.php">States</a>
                                <a class="dropdown-item" href="../super_admin/all_currency.php">Currency</a>
                                <a class="dropdown-item" href="../super_admin/all_account_types.php">Account Types</a>
                                <a class="dropdown-item" href="../super_admin/all_appointment_status.php">Appointment Types</a>
                                <a class="dropdown-item" href="../super_admin/all_business_types.php">Business Types</a>
                                <a class="dropdown-item" href="../super_admin/csv_uploader.php">CSV Uploader</a>
                                <a class="dropdown-item" href="../super_admin/all_chat_section.php">Chat Section</a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="javascript:" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Customization
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <a class="dropdown-item" href="../super_admin/all_enrollment_types.php">Enrollment Types</a>
                                <a class="dropdown-item" href="../super_admin/all_agreement_types.php">Agreement Types</a>
                                <a class="dropdown-item" href="../super_admin/all_payment_types.php">Payment Types</a>
                                <a class="dropdown-item" href="../super_admin/all_interests.php">Interests</a>
                                <a class="dropdown-item" href="../super_admin/all_document_types.php">Document Types</a>
                                <a class="dropdown-item" href="../super_admin/all_skill_levels.php">Skill Level</a>
                                <a class="dropdown-item" href="../super_admin/all_relationship.php">Relationship</a>
                                <a class="dropdown-item" href="../super_admin/all_service_class.php">Service Class</a>
                                <a class="dropdown-item" href="../super_admin/all_frequency.php">Frequency</a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="javascript:" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Communications
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <a class="dropdown-item" href="../super_admin/all_alert_messages.php">Alert Messages</a>
                                <a class="dropdown-item" href="../super_admin/all_email_triggers.php">Email Triggers</a>
                                <a class="dropdown-item" href="../super_admin/all_template_categories.php">Template Category</a>
                                <a class="dropdown-item" href="../super_admin/all_header_texts.php">Header Texts</a>
                                <a class="dropdown-item" href="../super_admin/manage_help_page.php">Help Pages</a>
                                <a class="dropdown-item" href="../super_admin/manage_help_category.php">Help Category</a>
                                <a class="dropdown-item" href="../super_admin/manage_help_sub_category.php">Help Subcategory</a>
                                <a class="dropdown-item" href="../super_admin/manage_help.php">Help</a>
                            </div>
                        </li>
                    </ul>
                    <div style="margin-left: 15%;">
                        <?php
                        $currentURL = $_SERVER['REQUEST_URI'];
                        $url = explode("/", $currentURL);
                        $address = $url[2];
                        if ($address == "all_rate_types.php" || $address == "all_users.php" || $address == "all_departments.php" || $address == "all_roles.php" || $address == "all_countries.php" || $address == "all_states.php"|| $address == "all_currency.php" || $address == "all_account_types.php" || $address == "all_appointment_status.php" || $address == "all_business_types.php" || $address == "csv_uploader.php") { ?>
                            <ul class="nav nav-tabs justify-content-center">
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_rate_types.php">Rate Types</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_users.php">Users</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_departments.php">Departments</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_roles.php">Roles</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_countries.php">Countries</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_states.php">States</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_currency.php">Currency</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_account_types.php">Account Types</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_appointment_status.php">Appointment Status</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_business_types.php">Business Types</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/csv_uploader.php">CSV Uploader</a></li>
                            </ul>
                        <?php } elseif ($address == "all_enrollment_types.php" || $address == "all_agreement_types.php" || $address == "all_payment_types.php" || $address == "all_interests.php" || $address == "all_document_types.php" || $address == "all_skill_levels.php" || $address == "all_relationship.php" || $address == "all_service_class.php" || $address == "all_frequency.php") { ?>
                            <ul class="nav nav-tabs justify-content-center">
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_enrollment_types.php">Enrollment Types</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_agreement_types.php">Agreement Types</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_payment_types.php">Payment Types</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_interests.php">Interests</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_document_types.php">Document Types</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_skill_levels.php">Skill Level</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_relationship.php">Relationship</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_service_class.php">Service Class</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_frequency.php">Frequency</a></li>
                            </ul>
                        <?php } elseif ($address == "all_alert_messages.php" || $address == "all_email_triggers.php" || $address == "all_template_categories.php" || $address == "all_header_texts.php" || $address == "manage_help_page.php" || $address == "manage_help_category.php" || $address == "manage_help_sub_category.php" || $address == "manage_help.php") { ?>
                            <ul class="nav nav-tabs justify-content-center">
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_alert_messages.php">Alert Messages</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_email_triggers.php">Email Triggers</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_template_categories.php">Template Category</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/all_header_texts.php">Header Texts</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/manage_help_page.php">Help Pages</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/manage_help_category.php">Help Category</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/manage_help_sub_category.php">Help Subcategory</a></li>
                                <li class="nav-item"><a class="nav-link" href="../super_admin/manage_help.php">Help</a></li>
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

