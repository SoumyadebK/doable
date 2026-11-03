<!DOCTYPE html>
<html lang="en">
<?php
require_once('../global/config.php');
$title = "Email Templates";

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Email Templates Page'");
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

// Get all email templates for these locations with user names
$templates_query = "SELECT t.*, 
                           u.FIRST_NAME as edited_first_name, 
                           u.LAST_NAME as edited_last_name,
                           u2.FIRST_NAME as created_first_name,
                           u2.LAST_NAME as created_last_name
                    FROM DOA_EMAIL_TEMPLATE t
                    LEFT JOIN $master_database.DOA_USERS u ON t.EDITED_BY = u.PK_USER
                    LEFT JOIN $master_database.DOA_USERS u2 ON t.CREATED_BY = u2.PK_USER
                    WHERE t.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " 
                    AND t.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")";
$all_templates = $db_account->Execute($templates_query);

// Build a map of templates by location and type
$template_map = [];
if ($all_templates && !$all_templates->EOF) {
    while (!$all_templates->EOF) {
        $loc_id = $all_templates->fields['PK_LOCATION'];
        $template_type = $all_templates->fields['TEMPLATE_NAME'];

        // Get user name for created_by
        $created_by_name = 'System';
        if (!empty($all_templates->fields['CREATED_BY']) && $all_templates->fields['CREATED_BY'] > 0) {
            $first_name = $all_templates->fields['created_first_name'] ?? '';
            $last_name = $all_templates->fields['created_last_name'] ?? '';
            if (!empty($first_name) || !empty($last_name)) {
                $created_by_name = trim($first_name . ' ' . $last_name);
            } else {
                $created_by_name = 'User #' . $all_templates->fields['CREATED_BY'];
            }
        }

        // Get user name for edited_by
        $edited_by_name = 'System';
        if (!empty($all_templates->fields['EDITED_BY']) && $all_templates->fields['EDITED_BY'] > 0) {
            $first_name = $all_templates->fields['edited_first_name'] ?? '';
            $last_name = $all_templates->fields['edited_last_name'] ?? '';
            if (!empty($first_name) || !empty($last_name)) {
                $edited_by_name = trim($first_name . ' ' . $last_name);
            } else {
                $edited_by_name = 'User #' . $all_templates->fields['EDITED_BY'];
            }
        }

        $template_map[$loc_id][$template_type] = [
            'PK_EMAIL_TEMPLATE' => $all_templates->fields['PK_EMAIL_TEMPLATE'],
            'TEMPLATE_NAME' => $all_templates->fields['TEMPLATE_NAME'],
            'SUBJECT' => $all_templates->fields['SUBJECT'],
            'ACTIVE' => $all_templates->fields['ACTIVE'],
            'CREATED_BY' => $all_templates->fields['CREATED_BY'],
            'CREATED_BY_NAME' => $created_by_name,
            'CREATED_ON' => $all_templates->fields['CREATED_ON'],
            'EDITED_BY' => $all_templates->fields['EDITED_BY'],
            'EDITED_BY_NAME' => $edited_by_name,
            'EDITED_ON' => $all_templates->fields['EDITED_ON']
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
                                <i class="bi bi-envelope me-2" style="color: #39b54a;"></i>Email Templates
                            </h2>
                            <p class="text-muted small mb-0">Manage email templates and their configurations</p>
                        </div>
                    </div>

                    <!-- Results count -->
                    <div class="text-muted small mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <?= $total_setup ?> of <?= $total_possible ?> set up
                    </div>

                    <!-- Email Templates List -->
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

                                        if ($template_data) {
                                            // Check if it's been edited (created_on and edited_on are different)
                                            $created_on = strtotime($template_data['CREATED_ON']);
                                            $edited_on = strtotime($template_data['EDITED_ON']);
                                            $is_edited = ($edited_on > $created_on);

                                            if ($is_edited) {
                                                $action_text = 'edited on';
                                                $action_date = date('j M', $edited_on);
                                                $action_by = $template_data['EDITED_BY_NAME'];
                                            } else {
                                                $action_text = 'created on';
                                                $action_date = date('j M', $created_on);
                                                $action_by = $template_data['CREATED_BY_NAME'];
                                            }
                                        } else {
                                            $action_text = '';
                                            $action_date = '';
                                            $action_by = '';
                                        }
                                    ?>
                                        <div class="template-item">
                                            <div>
                                                <span class="template-label"><?= htmlspecialchars($display_name) ?></span>
                                                <?php if ($is_setup && $template_data): ?>
                                                    <span class="template-edited"><?= $action_text ?> <?= $action_date ?> by <?= htmlspecialchars($action_by) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="template-status">
                                                <?php if ($is_setup): ?>
                                                    <span class="badge-status badge-active"><i class="bi bi-check-circle-fill"></i> Set up</span>
                                                    <a href="email_template.php?id=<?= $template_data['PK_EMAIL_TEMPLATE'] ?>" class="btn btn-setup setup">Edit</a>
                                                <?php else: ?>
                                                    <span class="badge-status badge-not-setup"><i class="bi bi-x-circle-fill"></i> Not set up</span>
                                                    <span class="text-muted small">no email will be sent</span>
                                                    <a href="email_template.php?location=<?= $loc_id ?>&type=<?= $type_key ?>" class="btn btn-setup">Set up</a>
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
            window.location.href = "email_template.php?id=" + id;
        }

        function setupTemplate(locationId, type) {
            window.location.href = "email_template.php?location=" + locationId + "&type=" + type;
        }
    </script>
</body>

</html>