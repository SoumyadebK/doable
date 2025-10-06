<?php
$mail_url = parse_url($_SERVER['REQUEST_URI']);
$url_array = explode("/", $mail_url['path']);
if ($_SERVER['HTTP_HOST'] == 'localhost') {
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
                    <div>
                        <?php
                        $currentURL = parse_url($_SERVER['REQUEST_URI']);
                        $url = explode("/", $currentURL['path']);
                        if ($_SERVER['HTTP_HOST'] == 'localhost') {
                            $address = $url[3];
                        } else {
                            $address = $url[2];
                        }
                        if ($address == "reports.php" || $address == "business_reports.php" || $address == "service_provider_reports.php" || $address == "electronic_miscellaneous_reports.php" || $address == "enrollment_reports.php" || $address == "customer_summary_report.php" || $address == "student_mailing_list.php" || $address == "total_open_liability.php" || $address == "active_account_balance_report.php" || $address == "cash_report.php" || $address == "sales_report.php") { ?>
                            <ul class="nav nav-pills justify-content-left">
                                <li class="nav-item"><a class="nav-link <?= ($address == 'reports.php') ? 'active' : '' ?>" href="../admin/reports.php">Electronic Weekly Reports</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'business_reports.php') ? 'active' : '' ?>" href="../admin/business_reports.php">Business Reports</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'service_provider_reports.php') ? 'active' : '' ?>" href="../admin/service_provider_reports.php">Service Provider Reports</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'electronic_miscellaneous_reports.php') ? 'active' : '' ?>" href="../admin/electronic_miscellaneous_reports.php">Electronic Miscellaneous Reports</a></li>
                                <!-- <li class="nav-item"><a class="nav-link <?= ($address == 'enrollment_reports.php') ? 'active' : '' ?>" href="../admin/enrollment_reports.php">Enrollment Reports</a></li> -->
                                <li class="nav-item"><a class="nav-link <?= ($address == 'customer_summary_report.php') ? 'active' : '' ?>" href="../admin/customer_summary_report.php">Customer Summary Report</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'student_mailing_list.php') ? 'active' : '' ?>" href="../admin/student_mailing_list.php">Student Mailing List</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'total_open_liability.php') ? 'active' : '' ?>" href="../admin/total_open_liability.php">Total Open Liability Since Last Activity</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'active_account_balance_report.php') ? 'active' : '' ?>" href="../admin/active_account_balance_report.php">Active Accounts Report</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'cash_report.php') ? 'active' : '' ?>" href="../admin/cash_report.php">Provider Cash Report</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'sales_report.php') ? 'active' : '' ?>" href="../admin/sales_report.php">Sales Report</a></li>
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
        display: inline-block;
        /* Display list items as inline-block */
        background-color: whitesmoke;
    }
</style>