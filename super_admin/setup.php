<?php
require_once('../global/config.php');
$title = "Setup";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    .menu-list{
        list-style-type: none;
        margin-left: -30px;
    }

    .menu-list li{
        margin: 10px;
    }
</style>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <?php require_once('../includes/setup_menu_super_admin.php') ?>
        <div class="container-fluid body_content m-0">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>

            <!--<div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="row" style="padding: 15px 35px 35px 35px;">
                            <div class="col-md-3 col-sm-3 mt-3">
                                <h4 class="card-title">General</h4>
                                <div>
                                    <ul class="menu-list">
                                        <li><a href="all_rate_types.php">Rate Types</a></li>
                                        <li><a href="all_users.php">Users</a></li>
                                        <li><a href="all_departments.php">Departments</a></li>
                                        <li><a href="all_roles.php">Roles</a></li>
                                        <li><a href="all_countries.php">Countries</a></li>
                                        <li><a href="all_states.php">States</a></li>
                                        <li><a href="all_currency.php">Currency</a></li>
                                        <li><a href="all_account_types.php">Account Types</a></li>
                                        <li><a href="all_appointment_status.php">Appointment Status</a></li>
                                        <li><a href="all_business_types.php">Business Types</a></li>
                                        <li><a href="csv_uploader.php">CSV Uploader</a></li>
                                        <!--<li><a href="all_scheduling_events.php">Scheduling Event</a></li>
                                        <li><a href="all_event_actions.php">Event Action</a></li>-->
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-3 mt-3">
                                <h4 class="card-title">Customization</h4>
                                <div>
                                    <ul class="menu-list">
                                        <li><a href="all_enrollment_types.php">Enrollment Types</a></li>
                                        <li><a href="all_agreement_types.php">Agreement Types</a></li>
                                        <li><a href="all_payment_types.php">Payment Types</a></li>
                                        <li><a href="all_interests.php">Interests</a></li>
                                        <li><a href="all_document_types.php">Document Types</a></li>
                                        <!--<li><a href="all_transaction_types.php">Transaction Types</a></li>-->
                                        <li><a href="all_skill_levels.php">Skill Level</a></li>
                                        <li><a href="all_relationship.php">Relationship</a></li>
                                        <li><a href="all_service_class.php">Service Class</a></li>
                                        <li><a href="all_frequency.php">Frequency</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-3 mt-3">
                                <h4 class="card-title">Communications</h4>
                                <div>
                                    <ul class="menu-list">
                                        <li><a href="all_alert_messages.php">Alert Messages</a></li>
                                        <li><a href="all_email_triggers.php">Email Triggers</a></li>
                                        <li><a href="all_template_categories.php">Template Category</a></li>
                                        <li><a href="all_header_texts.php">Header Texts</a></li>
                                        <li><a href="manage_help_category.php">Help Category</a></li>
                                        <li><a href="manage_help_sub_category.php">Help Subcategory</a></li>
                                        <li><a href="manage_help.php">Help</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>-->
        </div>
    </div>
</div>
<?php require_once('../includes/footer.php');?>
</body>
</html>