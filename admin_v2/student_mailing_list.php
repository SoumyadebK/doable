<?php
require_once('../global/config.php');
$title = "Student Mailing List";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Initialize filter variables
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$tag_filter = isset($_GET['tag_filter']) ? $_GET['tag_filter'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build WHERE conditions
$where_conditions = ["DOA_USER_ROLES.PK_ROLES = 4"];

// Add status filter
if ($status_filter !== '' && $status_filter !== 'all') {
    $where_conditions[] = "DOA_USERS.ACTIVE = " . intval($status_filter);
}

// Add tag filter
if ($tag_filter !== '' && $tag_filter !== 'all') {
    $where_conditions[] = "EXISTS (SELECT 1 FROM $account_database.DOA_USER_TAG AS DOA_USER_TAG WHERE DOA_USER_TAG.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER AND DOA_USER_TAG.PK_TAG = " . intval($tag_filter) . ")";
}

// Add date filter (assuming you have a date field - adjust column name as needed)
// This example uses CREATED_ON from DOA_USERS - adjust based on your actual table structure
if ($date_filter == 'range' && $start_date && $end_date) {
    $where_conditions[] = "DATE(DOA_USERS.CREATED_ON) BETWEEN '" . mysqli_real_escape_string($db->LinkID, $start_date) . "' AND '" . mysqli_real_escape_string($db->LinkID, $end_date) . "'";
} elseif ($date_filter == 'today') {
    $where_conditions[] = "DATE(DOA_USERS.CREATED_ON) = CURDATE()";
} elseif ($date_filter == 'week') {
    $where_conditions[] = "YEARWEEK(DOA_USERS.CREATED_ON) = YEARWEEK(CURDATE())";
} elseif ($date_filter == 'month') {
    $where_conditions[] = "MONTH(DOA_USERS.CREATED_ON) = MONTH(CURDATE()) AND YEAR(DOA_USERS.CREATED_ON) = YEAR(CURDATE())";
} elseif ($date_filter == 'year') {
    $where_conditions[] = "YEAR(DOA_USERS.CREATED_ON) = YEAR(CURDATE())";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get tags for dropdown
$tags_query = $db_account->Execute("SELECT PK_TAG, TAG_NAME FROM DOA_TAG WHERE ACTIVE = 1 ORDER BY TAG_NAME");
$tags = [];
while (!$tags_query->EOF) {
    $tags[] = ['PK_TAG' => $tags_query->fields['PK_TAG'], 'TAG_NAME' => $tags_query->fields['TAG_NAME']];
    $tags_query->MoveNext();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<style>
    /* Custom styles for the header */
    a {
        color: #690C24;
        text-decoration: none;
        font-size: 14px;
    }

    .btn {
        border: 0;
        color: #fff;
        border-radius: 50rem;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }

    input.form-control,
    select.form-control,
    textarea.form-control {
        border-radius: 0.375rem !important;
    }

    .filter-section {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .select2-container {
        width: 100% !important;
        height: 38px !important;
    }

    .select2-container .select2-selection--single {
        box-sizing: border-box;
        cursor: pointer;
        display: block;
        height: 38px;
        user-select: none;
        -webkit-user-select: none;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #444;
        line-height: 33px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 35px;
        position: absolute;
        top: 1px;
        right: 1px;
        width: 20px;
    }

    .filter-section .form-control,
    .filter-section .btn,
    .filter-section .select2-selection {
        border-radius: 50rem !important;
    }

    .btn-success {
        background-color: #39b54a;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">

        <div class="page-wrapper" style="padding-top: 1px !important;">

            <?php require_once('layout/report_menu.php') ?>
            <div class="container-fluid" style="padding: 10px 20px 0 20px; margin-top: 0px;">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-4">
                                        <img src="../assets/images/background/doable_logo.png" style="margin-bottom:15px; height: 60px; width: auto;">
                                    </div>
                                    <div class="col-4">
                                        <h3 class="card-title" style="padding-top:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                    </div>
                                    <div class="btn col-4">
                                        <form action="generate_excel.php" method="post" id="excel-form">
                                            <input type="hidden" name="filtered_data" id="filtered_data">
                                            <button type="submit" id="export-to-excel" name="ExportType"
                                                value="Export to Excel" class="btn btn-info">Export to Excel</button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Filter Section -->
                                <div class="filter-section justify-content-center align-items-center">
                                    <form method="GET" action="" id="filter-form">
                                        <div class="row">
                                            <!-- Date Filter -->
                                            <div class="col-md-1">
                                                <label class="form-label">Date Filter</label>
                                                <select name="date_filter" id="date_filter" class="form-control" onchange="toggleDateRange()">
                                                    <option value="">All Dates</option>
                                                    <option value="today" <?= $date_filter == 'today' ? 'selected' : '' ?>>Today</option>
                                                    <option value="week" <?= $date_filter == 'week' ? 'selected' : '' ?>>This Week</option>
                                                    <option value="month" <?= $date_filter == 'month' ? 'selected' : '' ?>>This Month</option>
                                                    <option value="year" <?= $date_filter == 'year' ? 'selected' : '' ?>>This Year</option>
                                                    <option value="range" <?= $date_filter == 'range' ? 'selected' : '' ?>>Date Range</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4" id="date_range_div" style="display: <?= $date_filter == 'range' ? 'block' : 'none' ?>;">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Start Date</label>
                                                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?= $start_date ?>">
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">End Date</label>
                                                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?= $end_date ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Status Filter -->
                                            <div class="col-md-1">
                                                <label class="form-label">Status</label>
                                                <select name="status_filter" id="status_filter" class="form-control">
                                                    <option value="all" <?= $status_filter == 'all' || $status_filter == '' ? 'selected' : '' ?>>All Status</option>
                                                    <option value="1" <?= $status_filter == '1' ? 'selected' : '' ?>>Active</option>
                                                    <option value="0" <?= $status_filter == '0' ? 'selected' : '' ?>>Inactive</option>
                                                </select>
                                            </div>

                                            <!-- Tag Filter Dropdown -->
                                            <div class="col-md-2">
                                                <label class="form-label">Tag Filter</label>
                                                <select name="tag_filter" id="tag_filter" class="form-control select2-tag">
                                                    <option value="all">Select Tag</option>
                                                    <?php foreach ($tags as $tag): ?>
                                                        <option value="<?= $tag['PK_TAG'] ?>" <?= $tag_filter == $tag['PK_TAG'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($tag['TAG_NAME']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-1">
                                                <label class="form-label">&nbsp;</label>
                                                <button type="submit" class="btn btn-success form-control">Apply</button>
                                            </div>

                                            <div class="col-md-1">
                                                <label class="form-label">&nbsp;</label>
                                                <a href="?reset=1" class="btn btn-secondary form-control">Reset</a>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="table-responsive">
                                    <table id="myTable" class="table table-bordered" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold">Last Name</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">First Name</th>
                                                <th style="width:15%; text-align: center; font-weight: bold">Partner Name</th>
                                                <th style="width:25%; text-align: center; font-weight: bold">Address</th>
                                                <th style="width:15%; text-align: center; font-weight: bold">City</th>
                                                <th style="width:5%; text-align: center; font-weight: bold">State</th>
                                                <th style="width:5%; text-align: center; font-weight: bold">Zip</th>
                                                <th style="width:15%; text-align: center; font-weight: bold">Email Address</th>
                                                <th style="width:5%; text-align: center; font-weight: bold">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;
                                            $query = "SELECT DOA_USERS.LAST_NAME, DOA_USERS.FIRST_NAME, CONCAT(DOA_CUSTOMER_DETAILS.PARTNER_FIRST_NAME, ' ', DOA_CUSTOMER_DETAILS.PARTNER_LAST_NAME) AS PARTNER_NAME, DOA_USERS.ADDRESS, DOA_USERS.CITY, DOA_STATES.STATE_NAME, DOA_USERS.ZIP, DOA_CUSTOMER_DETAILS.EMAIL, DOA_USERS.ACTIVE, DOA_USERS.CREATED_ON FROM DOA_USERS  INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN $account_database.DOA_CUSTOMER_DETAILS AS DOA_CUSTOMER_DETAILS ON DOA_CUSTOMER_DETAILS.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN DOA_STATES ON DOA_USERS.PK_STATES=DOA_STATES.PK_STATES $where_clause";

                                            $row = $db->Execute($query);

                                            if (!$row || $row->RecordCount() == 0) {
                                                echo '<tr><td colspan="9" style="text-align: center;">No records found</td></tr>';
                                            } else {
                                                while (!$row->EOF) {
                                                    $STATUS = ($row->fields['ACTIVE'] == 1) ? "Active" : "Inactive";
                                            ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row->fields['LAST_NAME']) ?></td>
                                                        <td><?= htmlspecialchars($row->fields['FIRST_NAME']) ?></td>
                                                        <td><?= htmlspecialchars($row->fields['PARTNER_NAME']) ?></td>
                                                        <td><?= htmlspecialchars($row->fields['ADDRESS']) ?></td>
                                                        <td><?= htmlspecialchars($row->fields['CITY']) ?></td>
                                                        <td style="text-align: center"><?= htmlspecialchars($row->fields['STATE_NAME']) ?></td>
                                                        <td style="text-align: center"><?= htmlspecialchars($row->fields['ZIP']) ?></td>
                                                        <td><?= htmlspecialchars($row->fields['EMAIL']) ?></td>
                                                        <td style="text-align: center">
                                                            <span class="badge <?= $STATUS == 'Active' ? 'bg-success' : 'bg-danger' ?>">
                                                                <?= $STATUS ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                            <?php
                                                    $row->MoveNext();
                                                    $i++;
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Show record count -->
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <p class="text-muted">Total Records: <?= $row ? $row->RecordCount() : 0 ?></p>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        $(function() {
            // Initialize Select2 for tag dropdown
            $('.select2-tag').select2({
                placeholder: "Select a tag",
                allowClear: true
            });
        });

        function toggleDateRange() {
            var dateFilter = document.getElementById('date_filter').value;
            var dateRangeDiv = document.getElementById('date_range_div');
            if (dateFilter === 'range') {
                dateRangeDiv.style.display = 'block';
            } else {
                dateRangeDiv.style.display = 'none';
                // Clear date fields when not in range mode
                document.getElementById('start_date').value = '';
                document.getElementById('end_date').value = '';
            }
        }

        // Handle export to Excel with filtered data
        document.getElementById('export-to-excel').addEventListener('click', function(e) {
            e.preventDefault();

            // Get all filter values
            var filters = {
                date_filter: document.getElementById('date_filter').value,
                start_date: document.getElementById('start_date').value,
                end_date: document.getElementById('end_date').value,
                status_filter: document.getElementById('status_filter').value,
                tag_filter: document.getElementById('tag_filter').value
            };

            // Create form and submit
            var form = document.getElementById('excel-form');
            var hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'filter_data';
            hiddenInput.value = JSON.stringify(filters);
            form.appendChild(hiddenInput);
            form.submit();
        });

        // Reset function
        <?php if (isset($_GET['reset'])): ?>
            window.location.href = window.location.pathname;
        <?php endif; ?>
    </script>

</body>

</html>