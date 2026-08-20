<!DOCTYPE html>
<html lang="en">
<?php
require_once('../global/config.php');
$title = "All SMS Templates";

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'SMS Templates Page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

// Get all locations - store in array
$locations_data = [];
$locations_query = "SELECT PK_LOCATION, LOCATION_NAME FROM $master_database.DOA_LOCATION WHERE ACTIVE = 1 AND PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']);
$locations = $db->Execute($locations_query);
if ($locations && !$locations->EOF) {
    while (!$locations->EOF) {
        $locations_data[] = [
            'PK_LOCATION' => $locations->fields['PK_LOCATION'],
            'LOCATION_NAME' => $locations->fields['LOCATION_NAME']
        ];
        $locations->MoveNext();
    }
}

// Get all SMS templates for these locations
$templates_query = "SELECT * FROM DOA_SMS_TEMPLATE 
                    WHERE PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " AND PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")";
$all_templates = $db_account->Execute($templates_query);

// Build a map of templates by location and type
$template_map = [];
if ($all_templates && !$all_templates->EOF) {
    while (!$all_templates->EOF) {
        $loc_id = $all_templates->fields['PK_LOCATION'];
        $template_type = $all_templates->fields['TEMPLATE_NAME']; // Use TEMPLATE_NAME field
        $template_map[$loc_id][$template_type] = [
            'PK_SMS_TEMPLATE' => $all_templates->fields['PK_SMS_TEMPLATE'],
            'TEMPLATE_NAME' => $all_templates->fields['TEMPLATE_NAME'],
            'CONTENT' => $all_templates->fields['CONTENT'],
            'ACTIVE' => $all_templates->fields['ACTIVE'],
            'UPDATED_BY' => $all_templates->fields['EDITED_BY'],
            'UPDATED_DATE' => $all_templates->fields['EDITED_ON']
        ];
        $all_templates->MoveNext();
    }
}

// Define template types - using the actual TEMPLATE_NAME values from your database
$template_types = [
    'Enrollment Creation' => 'ENROLLMENT_CREATION',
    'Appointment Creation' => 'APPOINTMENT_CREATION'
];

// Count total set up
$total_setup = 0;
$total_possible = 0;
foreach ($locations_data as $location) {
    $loc_id = $location['PK_LOCATION'];
    foreach ($template_types as $display_name => $type_key) {
        $total_possible++;
        if (isset($template_map[$loc_id][$type_key]) && $template_map[$loc_id][$type_key]['ACTIVE'] == 1) {
            $total_setup++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Setup Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/setup-styles.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
        .badge-status {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-active {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-inactive {
            background: #f1f5f9;
            color: #64748b;
        }

        .badge-not-setup {
            background: #fee2e2;
            color: #b91c1c;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .action-icons {
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: flex-start;
        }

        .action-icons a {
            color: #64748b;
            transition: color 0.2s;
            font-size: 1.1rem;
        }

        .action-icons a:hover {
            color: #0d6efd;
        }

        .template-group {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .template-group-header {
            background: #f8fafc;
            padding: 10px 16px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
        }

        .template-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        .template-item:last-child {
            border-bottom: none;
        }

        .template-label {
            font-weight: 500;
            color: #1e293b;
        }

        .template-status {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-setup {
            padding: 4px 16px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 500;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-setup:hover {
            background: #0d6efd;
            color: white;
            border-color: #0d6efd;
            text-decoration: none;
        }

        .btn-setup.setup {
            background: #dcfce7;
            color: #15803d;
            border-color: #86efac;
        }

        .btn-setup.setup:hover {
            background: #15803d;
            color: white;
            border-color: #15803d;
        }

        .status-icon {
            font-size: 1.1rem;
        }

        .location-section {
            margin-bottom: 24px;
        }

        .location-title {
            font-weight: 600;
            color: #0f172a;
            font-size: 1.1rem;
            margin-bottom: 12px;
        }

        .template-edited {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-left: 8px;
        }

        .setup-count {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 400;
        }

        @media (max-width: 768px) {
            .template-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .template-status {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
            }
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
        }

        .main-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            padding: 24px;
            border: 1px solid #e2e8f0;
        }

        .btn-success-custom {
            background: #39b54a;
            color: white;
            border: none;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-success-custom:hover {
            background: #2d8f3b;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(57, 181, 74, 0.2);
        }

        .search-container {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-container i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .search-input {
            padding-left: 38px;
            border-radius: 30px;
            border: 1.5px solid #e2e8f0;
            padding: 8px 16px 8px 38px;
            font-size: 14px;
            width: 100%;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: #39b54a;
            box-shadow: 0 0 0 3px rgba(57, 181, 74, 0.1);
            outline: none;
        }

        .status-toggle-group {
            display: flex;
            gap: 4px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 30px;
        }

        .status-btn {
            padding: 6px 16px;
            border: none;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            background: transparent;
            color: #64748b;
            transition: all 0.2s;
            cursor: pointer;
        }

        .status-btn.active {
            background: white;
            color: #0f172a;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .status-btn:hover:not(.active) {
            color: #0f172a;
        }

        .text-muted {
            color: #94a3b8 !important;
        }

        .fw-semibold {
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-12 col-md-4 col-xl-2">
                <?php include 'layout/setup_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-12 col-md-8 col-xl-10">
                <div class="main-card">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
                        <div>
                            <h2 class="fw-semibold h4 mb-1">
                                <i class="bi bi-chat-dots me-2" style="color: #39b54a;"></i>SMS Templates
                            </h2>
                            <p class="text-muted small mb-0">Manage SMS templates and their configurations</p>
                        </div>
                    </div>

                    <!-- Results count -->
                    <div class="text-muted small mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <?= $total_setup ?> of <?= $total_possible ?> set up
                    </div>

                    <!-- SMS Templates List -->
                    <?php if (!empty($locations_data)): ?>
                        <?php foreach ($locations_data as $location):
                            $loc_id = $location['PK_LOCATION'];
                            $loc_name = $location['LOCATION_NAME'];
                        ?>
                            <div class="location-section">
                                <div class="location-title"><?= htmlspecialchars($loc_name) ?></div>
                                <div class="template-group">
                                    <?php foreach ($template_types as $display_name => $type_key):
                                        $is_setup = isset($template_map[$loc_id][$type_key]) && $template_map[$loc_id][$type_key]['ACTIVE'] == 1;
                                        $template_data = isset($template_map[$loc_id][$type_key]) ? $template_map[$loc_id][$type_key] : null;
                                        $edited_by = $template_data ? $template_data['UPDATED_BY'] : 'Demo';
                                        $edited_date = $template_data ? date('j M', strtotime($template_data['UPDATED_DATE'])) : date('j M');
                                    ?>
                                        <div class="template-item">
                                            <div>
                                                <span class="template-label"><?= htmlspecialchars($display_name) ?></span>
                                                <?php if ($is_setup): ?>
                                                    <span class="template-edited">edited <?= $edited_date ?> by <?= htmlspecialchars($edited_by) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="template-status">
                                                <?php if ($is_setup): ?>
                                                    <span class="badge-status badge-active"><i class="bi bi-check-circle-fill"></i> Set up</span>
                                                    <a href="sms_template.php?id=<?= $template_data['PK_SMS_TEMPLATE'] ?>" class="btn btn-setup setup">Edit</a>
                                                <?php else: ?>
                                                    <span class="badge-status badge-not-setup"><i class="bi bi-x-circle-fill"></i> Not set up</span>
                                                    <span class="text-muted small">no SMS will be sent</span>
                                                    <a href="sms_template.php?location=<?= $loc_id ?>&type=<?= $type_key ?>" class="btn btn-setup">Set up</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-building"></i>
                            <p class="mt-3 text-muted">No locations found. Please add locations first.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        function editTemplate(id) {
            window.location.href = "sms_template.php?id=" + id;
        }

        function setupTemplate(locationId, type) {
            window.location.href = "sms_template.php?location=" + locationId + "&type=" + type;
        }
    </script>
</body>

</html>