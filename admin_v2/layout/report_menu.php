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
    .reports-nav-container {
        background: white;
        border-radius: 40px;
        margin: 16px 24px;
        padding: 8px 0;
        border: 1px solid #eaecf0;
    }

    .reports-nav-wrapper {
        padding: 0 16px;
    }

    .reports-nav-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        background: #fff;
        border-radius: 40px;
        padding: 4px;
    }

    .reports-nav-btn {
        border: none;
        background: transparent;
        color: #667085;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 6px 20px;
        border-radius: 40px;
        transition: all 0.2s ease;
        cursor: pointer;
        white-space: nowrap;
    }

    .reports-nav-btn:hover {
        background-color: #f9fafb;
        color: #344054;
    }

    .reports-nav-btn.active {
        background-color: #39b54a;
        color: white;
    }

    @media (max-width: 768px) {
        .reports-nav-group {
            flex-wrap: wrap;
        }

        .reports-nav-btn {
            padding: 4px 12px;
            font-size: 0.75rem;
        }

        .reports-nav-container {
            margin: 12px;
        }
    }
</style>

<div class="reports-nav-container">
    <div class="reports-nav-wrapper">
        <div class="reports-nav-group">
            <?php
            // Define active groups with multiple files
            $active_groups = [
                'weekly_reports' => ['reports.php'],
                'business_reports' => ['business_reports.php'],
                'service_provider_reports' => ['service_provider_reports.php'],
                'student_mailing_list' => ['student_mailing_list.php'],
                'total_open_liability' => ['total_open_liability.php'],
                'customer_reports' => ['active_account_balance_report.php', 'active_account_balance_report_details.php', 'customer_summary_report.php'],
                'sales_report' => ['sales_report.php']
            ];

            // Find which group current page belongs to
            $current_group = '';
            foreach ($active_groups as $group => $files) {
                if (in_array($current_address, $files)) {
                    $current_group = $group;
                    break;
                }
            }

            $nav_items = [
                'reports.php' => ['label' => 'Weekly Reports', 'group' => 'weekly_reports'],
                'business_reports.php' => ['label' => 'Business Reports', 'group' => 'business_reports'],
                'service_provider_reports.php' => ['label' => 'Service Provider Reports', 'group' => 'service_provider_reports'],
                'student_mailing_list.php' => ['label' => 'Student Mailing List', 'group' => 'student_mailing_list'],
                'total_open_liability.php' => ['label' => 'Total Open Liability', 'group' => 'total_open_liability'],
                'active_account_balance_report.php' => ['label' => 'Customer Reports', 'group' => 'customer_reports'],

                'sales_report.php' => ['label' => 'Sales Report', 'group' => 'sales_report']
            ];

            foreach ($nav_items as $file => $item):
                $is_active = ($current_group == $item['group']) ? 'active' : '';
            ?>
                <button class="reports-nav-btn <?= $is_active ?>" onclick="window.location.href='../admin_v2/<?= $file ?>'">
                    <?= htmlspecialchars($item['label']) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>