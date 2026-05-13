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
            $nav_items = [
                'reports.php' => 'Weekly Reports',
                'business_reports.php' => 'Business Reports',
                'service_provider_reports.php' => 'Service Provider Reports',
                'student_mailing_list.php' => 'Student Mailing List',
                'total_open_liability.php' => 'Total Open Liability',
                'active_account_balance_report.php' => 'Customer Reports',
                'sales_report.php' => 'Sales Report'
            ];

            foreach ($nav_items as $file => $label):
                $is_active = ($current_address == $file) ? 'active' : '';
            ?>
                <button class="reports-nav-btn <?= $is_active ?>" onclick="window.location.href='../admin_v2/<?= $file ?>'">
                    <?= htmlspecialchars($label) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>