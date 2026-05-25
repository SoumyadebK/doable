<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Leads";
else
    $title = "Edit Leads";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '') {
    header("location:../login.php");
    exit;
}

// Capture ALL filter parameters from URL (GET) to preserve them when going back
$filter_start_date = isset($_GET['filter_start_date']) ? $_GET['filter_start_date'] : '';
$filter_end_date = isset($_GET['filter_end_date']) ? $_GET['filter_end_date'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_search = isset($_GET['filter_search']) ? $_GET['filter_search'] : '';
$filter_page = isset($_GET['filter_page']) ? $_GET['filter_page'] : '';
// Also capture grid filters (from leads_grid.php)
$grid_status = isset($_GET['status']) ? $_GET['status'] : '';
$grid_search_text = isset($_GET['search_text']) ? $_GET['search_text'] : '';
$grid_choose_date = isset($_GET['CHOOSE_DATE']) ? $_GET['CHOOSE_DATE'] : '';
$grid_sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : '';
// Capture date range filters
$grid_date_from = isset($_GET['DATE_FROM']) ? $_GET['DATE_FROM'] : '';
$grid_date_to = isset($_GET['DATE_TO']) ? $_GET['DATE_TO'] : '';

if (!empty($_POST)) {
    $IP_ADDRESS = getUserIP();
    if ($IP_ADDRESS == '35.161.112.234') {
        header("location:all_leads.php");
        exit;
    }

    if (empty($_GET['id'])) {
        $LEADS_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
        $LEADS_DATA['FIRST_NAME'] = $_POST['FIRST_NAME'];
        $LEADS_DATA['LAST_NAME'] = $_POST['LAST_NAME'];
        $LEADS_DATA['PHONE'] = $_POST['PHONE'];
        $LEADS_DATA['EMAIL_ID'] = $_POST['EMAIL_ID'];
        $LEADS_DATA['PK_LEAD_STATUS'] = $_POST['PK_LEAD_STATUS'];
        $LEADS_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
        $LEADS_DATA['OPPORTUNITY_SOURCE'] = $_POST['OPPORTUNITY_SOURCE'];
        $LEADS_DATA['REMOTE_ADDRESS'] = $_SERVER['REMOTE_ADDR'];
        $LEADS_DATA['IP_ADDRESS'] = $IP_ADDRESS;
        $LEADS_DATA['ACTIVE'] = 1;
        $LEADS_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $LEADS_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_LEADS', $LEADS_DATA, 'insert');
        $PK_LEADS = $db->insert_ID();
    } else {
        $LEADS_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
        $LEADS_DATA['FIRST_NAME'] = $_POST['FIRST_NAME'];
        $LEADS_DATA['LAST_NAME'] = $_POST['LAST_NAME'];
        $LEADS_DATA['PHONE'] = $_POST['PHONE'];
        $LEADS_DATA['EMAIL_ID'] = $_POST['EMAIL_ID'];
        $LEADS_DATA['PK_LEAD_STATUS'] = $_POST['PK_LEAD_STATUS'];
        $LEADS_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
        $LEADS_DATA['OPPORTUNITY_SOURCE'] = $_POST['OPPORTUNITY_SOURCE'];
        $LEADS_DATA['REMOTE_ADDRESS'] = $_SERVER['REMOTE_ADDR'];
        $LEADS_DATA['IP_ADDRESS'] = $IP_ADDRESS;
        $LEADS_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $LEADS_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $LEADS_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_LEADS', $LEADS_DATA, 'update', " PK_LEADS =  '$_GET[id]'");
        $PK_LEADS = $_GET['id'];
    }

    if (!empty($PK_LEADS) && !empty($_POST['DATE'])) {
        $LEAD_DATE = array(
            'PK_LEADS' => $PK_LEADS,
            'PK_LEAD_STATUS' => $_POST['PK_LEAD_STATUS'],
            'DATE' => date("Y-m-d", strtotime($_POST['DATE'])),
            'COMMENT' => $_POST['FOLLOW_UP_COMMENT'],
            'CREATED_BY' => $_SESSION['PK_USER'],
            'CREATED_ON' => date("Y-m-d H:i")
        );
        db_perform('DOA_LEAD_DATE', $LEAD_DATE, 'insert');
    }

    // Build redirect URL with preserved filters from POST or original GET
    $redirect_url = "leads_grid.php";
    $preserve_params = array();

    // Check all possible filter parameters from POST (hidden inputs)
    $filter_params = [
        'filter_start_date',
        'filter_end_date',
        'filter_status',
        'filter_search',
        'filter_page',
        'status',
        'search_text',
        'CHOOSE_DATE',
        'sort_by',
        'DATE_FROM',
        'DATE_TO'
    ];

    foreach ($filter_params as $param) {
        if (isset($_POST[$param]) && $_POST[$param] !== '') {
            $preserve_params[$param] = $_POST[$param];
        } elseif (isset($_GET[$param]) && $_GET[$param] !== '') {
            $preserve_params[$param] = $_GET[$param];
        }
    }

    if (!empty($preserve_params)) {
        $redirect_url .= "?" . http_build_query($preserve_params);
    }

    header("location:" . $redirect_url);
    exit;
}

// Handle AJAX request to get follow-up data for a specific status
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_status_data' && isset($_GET['lead_id']) && isset($_GET['status_id'])) {
    $lead_id = $_GET['lead_id'];
    $status_id = $_GET['status_id'];

    $data_res = $db->Execute("
        SELECT DATE, COMMENT 
        FROM `DOA_LEAD_DATE` 
        WHERE PK_LEADS = '$lead_id' AND PK_LEAD_STATUS = '$status_id'
        ORDER BY CREATED_ON DESC 
        LIMIT 1
    ");

    $data = array();
    if ($data_res->RecordCount() > 0) {
        $data['date'] = !empty($data_res->fields['DATE']) ? date('m/d/Y', strtotime($data_res->fields['DATE'])) : '';
        $data['comment'] = $data_res->fields['COMMENT'];
    } else {
        $data['date'] = '';
        $data['comment'] = '';
    }

    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (empty($_GET['id'])) {
    $PK_LOCATION = '';
    $FIRST_NAME = '';
    $LAST_NAME = '';
    $PHONE = '';
    $EMAIL_ID = '';
    $PK_LEAD_STATUS = '';
    $DATE = '';
    $COMMENT = '';
    $DESCRIPTION = '';
    $OPPORTUNITY_SOURCE = '';
    $ACTIVE = '';
    $status_logs = array();
} else {
    $res = $db->Execute("SELECT * FROM `DOA_LEADS` WHERE PK_LEADS = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        $redirect_url = "leads_grid.php";
        $preserve_params = array();
        $filter_params = ['status', 'search_text', 'CHOOSE_DATE', 'sort_by', 'DATE_FROM', 'DATE_TO', 'filter_start_date', 'filter_end_date', 'filter_status', 'filter_search', 'filter_page'];
        foreach ($filter_params as $param) {
            if (isset($_GET[$param]) && $_GET[$param] !== '') {
                $preserve_params[$param] = $_GET[$param];
            }
        }
        if (!empty($preserve_params)) {
            $redirect_url .= "?" . http_build_query($preserve_params);
        }
        header("location:" . $redirect_url);
        exit;
    }

    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $FIRST_NAME = $res->fields['FIRST_NAME'];
    $LAST_NAME = $res->fields['LAST_NAME'];
    $PHONE = $res->fields['PHONE'];
    $EMAIL_ID = $res->fields['EMAIL_ID'];
    $PK_LEAD_STATUS = $res->fields['PK_LEAD_STATUS'];
    $OPPORTUNITY_SOURCE = $res->fields['OPPORTUNITY_SOURCE'];
    $DESCRIPTION = $res->fields['DESCRIPTION'];
    $ACTIVE = $res->fields['ACTIVE'];

    // Get the latest lead date and comment for the CURRENT status
    $date_res = $db->Execute("
        SELECT DATE, COMMENT 
        FROM `DOA_LEAD_DATE` 
        WHERE PK_LEADS = '$_GET[id]' AND PK_LEAD_STATUS = '$PK_LEAD_STATUS'
        ORDER BY CREATED_ON DESC 
        LIMIT 1
    ");
    if ($date_res->RecordCount() > 0) {
        $DATE = !empty($date_res->fields['DATE']) ? date("m/d/Y", strtotime($date_res->fields['DATE'])) : '';
        $COMMENT = $date_res->fields['COMMENT'];
    } else {
        $DATE = '';
        $COMMENT = '';
    }

    // Get all status logs for this lead
    $status_logs = $db->Execute("
        SELECT ld.*, ls.LEAD_STATUS
        FROM `DOA_LEAD_DATE` ld 
        LEFT JOIN DOA_LEAD_STATUS ls ON ld.PK_LEAD_STATUS = ls.PK_LEAD_STATUS
        WHERE ld.PK_LEADS = '$_GET[id]' 
        ORDER BY ld.CREATED_ON DESC
    ");
}

// Get lead statuses for dropdown
$lead_statuses = $db->Execute("SELECT * FROM `DOA_LEAD_STATUS` WHERE ACTIVE = 1 AND `PK_ACCOUNT_MASTER` = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DISPLAY_ORDER ASC");
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <style>
        a {
            color: #690C24;
            text-decoration: none;
            font-size: 14px;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
        }

        .btn-success {
            background-color: #39b54a;
            border-color: #39b54a;
        }

        .btn-success:hover {
            background-color: #2e8e3c;
            border-color: #2e8e3c;
        }

        .form-label {
            font-weight: 500;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            font-size: 0.85rem;
            border: 1px solid #dee2e6;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #39b54a;
            box-shadow: 0 0 0 0.2rem rgba(57, 181, 74, 0.25);
        }

        .status-log-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            background: #f8f9fa;
        }

        .status-log-item {
            padding: 10px;
            margin-bottom: 8px;
            background: white;
            border-radius: 6px;
            border-left: 3px solid #39b54a;
        }

        .status-log-item:last-child {
            margin-bottom: 0;
        }

        .status-log-item strong {
            font-size: 0.85rem;
        }

        .status-log-item .text-muted {
            font-size: 0.7rem;
        }

        #selected_status_label,
        #selected_status_comment_label {
            font-weight: bold;
            color: #39b54a;
        }

        .icon-circle {
            width: 45px;
            height: 45px;
            text-align: center;
            border-radius: 25px;
            background-color: #e8f5e9;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-circle i {
            font-size: 1.25rem;
            color: #39b54a;
        }

        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .card-body {
            padding: 1.5rem;
        }
    </style>
</head>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid body_content" style="margin-top: 0px;">
                <div class="container-fluid py-4 px-4 bg-white m-3 rounded border mx-auto">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <div class="p-2 bg-white border me-3 icon-circle">
                                <i class="bi bi-person-plus fs-5"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold"><?= $title ?></h4>
                                <p class="text-muted small mb-0"><?= empty($_GET['id']) ? 'Add a new lead to the system' : 'Edit lead information and track status' ?></p>
                            </div>
                        </div>
                        <button class="btn btn-success border-0 rounded-pill px-3" onclick="goBackToLeads()">
                            <i class="bi bi-arrow-left me-1"></i> Back to Leads
                        </button>
                    </div>

                    <form class="form-material form-horizontal" name="form1" id="form1" action="" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="PK_LEADS" id="PK_LEADS" value="<?= $_GET['id'] ?? '' ?>" />

                        <!-- Hidden fields to preserve ALL filters when saving -->
                        <?php if (!empty($filter_start_date)): ?>
                            <input type="hidden" name="filter_start_date" value="<?= htmlspecialchars($filter_start_date) ?>">
                        <?php endif; ?>
                        <?php if (!empty($filter_end_date)): ?>
                            <input type="hidden" name="filter_end_date" value="<?= htmlspecialchars($filter_end_date) ?>">
                        <?php endif; ?>
                        <?php if (!empty($filter_status)): ?>
                            <input type="hidden" name="filter_status" value="<?= htmlspecialchars($filter_status) ?>">
                        <?php endif; ?>
                        <?php if (!empty($filter_search)): ?>
                            <input type="hidden" name="filter_search" value="<?= htmlspecialchars($filter_search) ?>">
                        <?php endif; ?>
                        <?php if (!empty($filter_page)): ?>
                            <input type="hidden" name="filter_page" value="<?= htmlspecialchars($filter_page) ?>">
                        <?php endif; ?>
                        <!-- Grid filter parameters -->
                        <?php if (!empty($grid_status)): ?>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($grid_status) ?>">
                        <?php endif; ?>
                        <?php if (!empty($grid_search_text)): ?>
                            <input type="hidden" name="search_text" value="<?= htmlspecialchars($grid_search_text) ?>">
                        <?php endif; ?>
                        <?php if (!empty($grid_choose_date)): ?>
                            <input type="hidden" name="CHOOSE_DATE" value="<?= htmlspecialchars($grid_choose_date) ?>">
                        <?php endif; ?>
                        <?php if (!empty($grid_sort_by)): ?>
                            <input type="hidden" name="sort_by" value="<?= htmlspecialchars($grid_sort_by) ?>">
                        <?php endif; ?>
                        <!-- Date range filters -->
                        <?php if (!empty($grid_date_from)): ?>
                            <input type="hidden" name="DATE_FROM" value="<?= htmlspecialchars($grid_date_from) ?>">
                        <?php endif; ?>
                        <?php if (!empty($grid_date_to)): ?>
                            <input type="hidden" name="DATE_TO" value="<?= htmlspecialchars($grid_date_to) ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Location <span class="text-danger">*</span></label>
                                    <select class="form-select" name="PK_LOCATION" id="PK_LOCATION" required>
                                        <option value="">Select Location</option>
                                        <?php
                                        $locations = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                        while (!$locations->EOF) { ?>
                                            <option value="<?php echo $locations->fields['PK_LOCATION']; ?>" <?= ($locations->fields['PK_LOCATION'] == $PK_LOCATION) ? "selected" : "" ?>><?= $locations->fields['LOCATION_NAME'] ?></option>
                                        <?php
                                            $locations->MoveNext();
                                        } ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" value="<?= htmlspecialchars($FIRST_NAME) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name" value="<?= htmlspecialchars($LAST_NAME) ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <?php if (empty($_GET['id'])) { ?>
                                        <input type="text" id="PHONE" name="PHONE" class="form-control format_phone_number" placeholder="Enter Phone Number">
                                    <?php } else { ?>
                                        <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone Number" value="<?php echo formatPhone($PHONE) ?>">
                                    <?php } ?>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" value="<?= htmlspecialchars($EMAIL_ID) ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lead Status <span class="text-danger">*</span></label>
                                    <select class="form-select" name="PK_LEAD_STATUS" id="PK_LEAD_STATUS" required onchange="loadStatusData()">
                                        <?php
                                        $lead_statuses = $db->Execute("SELECT * FROM `DOA_LEAD_STATUS` WHERE ACTIVE = 1 AND `PK_ACCOUNT_MASTER` = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DISPLAY_ORDER ASC");
                                        while (!$lead_statuses->EOF) { ?>
                                            <option value="<?php echo $lead_statuses->fields['PK_LEAD_STATUS']; ?>" <?= ($lead_statuses->fields['PK_LEAD_STATUS'] == $PK_LEAD_STATUS) ? 'selected' : '' ?>><?= $lead_statuses->fields['LEAD_STATUS'] ?></option>
                                        <?php $lead_statuses->MoveNext();
                                        } ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Opportunity Source</label>
                                    <input type="text" id="OPPORTUNITY_SOURCE" name="OPPORTUNITY_SOURCE" class="form-control" placeholder="Enter Opportunity Source" value="<?php echo htmlspecialchars($OPPORTUNITY_SOURCE) ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Remarks</label>
                                    <textarea class="form-control" id="DESCRIPTION" name="DESCRIPTION" rows="3" placeholder="Enter lead description"><?= htmlspecialchars($DESCRIPTION) ?></textarea>
                                </div>

                                <?php if (!empty($_GET['id'])) { ?>
                                    <div class="mb-3">
                                        <label class="form-label d-block">Active</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="ACTIVE" id="ACTIVE_YES" value="1" <?= ($ACTIVE == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="ACTIVE_YES">Yes</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="ACTIVE" id="ACTIVE_NO" value="0" <?= ($ACTIVE == 0) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="ACTIVE_NO">No</label>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <hr class="my-3">
                                <h6 class="fw-bold mb-3"><i class="bi bi-calendar-check me-2"></i> Follow-up Information</h6>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Follow up Date for <span id="selected_status_label">Selected Status</span></label>
                                    <input type="text" id="DATE" name="DATE" class="form-control datepicker-normal" placeholder="Select Follow up Date" value="<?php echo $DATE ?>">
                                    <small class="text-muted">Select date for follow-up</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Comment for <span id="selected_status_comment_label">Selected Status</span></label>
                                    <textarea class="form-control" name="FOLLOW_UP_COMMENT" id="FOLLOW_UP_COMMENT" rows="1" placeholder="Enter comment for this follow up"><?= htmlspecialchars($COMMENT) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($_GET['id'])): ?>
                            <div class="row mt-3">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="bi bi-clock-history me-2"></i> Status Log</label>
                                        <div class="status-log-container">
                                            <?php if ($status_logs && $status_logs->RecordCount() > 0): ?>
                                                <?php while (!$status_logs->EOF): ?>
                                                    <div class="status-log-item">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <strong><?= htmlspecialchars($status_logs->fields['LEAD_STATUS'] ?? 'N/A') ?></strong>
                                                            <small class="text-muted">
                                                                <?= !empty($status_logs->fields['DATE']) ? date('m/d/Y', strtotime($status_logs->fields['DATE'])) : 'No date' ?>
                                                            </small>
                                                        </div>
                                                        <?php if (!empty($status_logs->fields['COMMENT'])): ?>
                                                            <p class="mb-1 small mt-1"><?= htmlspecialchars($status_logs->fields['COMMENT']) ?></p>
                                                        <?php endif; ?>
                                                        <small class="text-muted">Added on <?= date('m/d/Y H:i', strtotime($status_logs->fields['CREATED_ON'])) ?></small>
                                                    </div>
                                                <?php
                                                    $status_logs->MoveNext();
                                                endwhile; ?>
                                            <?php else: ?>
                                                <p class="text-muted mb-0 text-center py-3">No status logs available</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="row mt-4">
                            <div class="col-12">
                                <hr class="my-3">
                                <button type="submit" class="btn btn-success rounded-pill px-4">
                                    <i class="bi bi-check-lg me-1"></i> Save
                                </button>
                                <button type="button" class="btn btn-secondary rounded-pill px-4 ms-2" onclick="goBackToLeads()">
                                    <i class="bi bi-x-lg me-1"></i> Cancel
                                </button>
                                <a href="javascript:;" onclick="createCustomer()" class="btn btn-info rounded-pill px-4 ms-2 text-white">
                                    <i class="bi bi-person-bounding-box me-1"></i> Create Customer
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function goBackToLeads() {
            // Build URL with all preserved filter parameters from URL
            let urlParams = new URLSearchParams(window.location.search);
            let filters = [];

            // Preserve ALL possible filter parameters including date range
            let paramsToPreserve = [
                'filter_start_date', 'filter_end_date', 'filter_status', 'filter_search', 'filter_page',
                'status', 'search_text', 'CHOOSE_DATE', 'sort_by', 'DATE_FROM', 'DATE_TO'
            ];

            for (let param of paramsToPreserve) {
                if (urlParams.has(param) && urlParams.get(param) !== '') {
                    filters.push(param + '=' + encodeURIComponent(urlParams.get(param)));
                }
            }

            let url = 'leads_grid.php';
            if (filters.length > 0) {
                url += '?' + filters.join('&');
            }

            window.location.href = url;
        }

        function formatPhoneNumber(input) {
            let digits = input.value.replace(/\D/g, '');
            if (digits.length > 10) {
                digits = digits.slice(0, 10);
            }
            let formatted = digits;

            if (digits.length <= 3) {
                formatted = digits;
            } else if (digits.length <= 6) {
                formatted = `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
            } else {
                formatted = `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
            }

            input.value = formatted;
        }

        $(document).on('input', '.format_phone_number', function() {
            formatPhoneNumber(this);
        });

        $(document).ready(function() {
            $('.datepicker-normal').datepicker({
                dateFormat: 'mm/dd/yy',
                changeMonth: true,
                changeYear: true
            });
        });

        function createCustomer() {
            let PK_LEADS = $('#PK_LEADS').val();
            let PK_LOCATION = $('#PK_LOCATION').val();
            let FIRST_NAME = $('#FIRST_NAME').val();
            let LAST_NAME = $('#LAST_NAME').val();
            let PHONE = $('#PHONE').val();
            let EMAIL_ID = $('#EMAIL_ID').val();
            let NOTES = $('#DESCRIPTION').val();

            if (!PK_LOCATION || !FIRST_NAME) {
                Swal.fire('Error', 'Please select location and enter first name before creating customer', 'error');
                return;
            }

            window.location.href = `../admin/customer.php?PK_LOCATION=${encodeURIComponent(PK_LOCATION)}&FIRST_NAME=${encodeURIComponent(FIRST_NAME)}&LAST_NAME=${encodeURIComponent(LAST_NAME)}&PHONE=${encodeURIComponent(PHONE)}&EMAIL_ID=${encodeURIComponent(EMAIL_ID)}&PK_LEADS=${PK_LEADS}&NOTES=${encodeURIComponent(NOTES)}`;
        }

        function loadStatusData() {
            let leadId = $('#PK_LEADS').val();
            let currentStatusId = $('#PK_LEAD_STATUS').val();
            let currentStatusText = $('#PK_LEAD_STATUS option:selected').text();

            let nextStatusId = getNextStatusId(currentStatusId);
            let nextStatusText = getNextStatusText(currentStatusId);

            if (nextStatusText) {
                $('#selected_status_label').text(nextStatusText);
                $('#selected_status_comment_label').text(nextStatusText);
            } else {
                $('#selected_status_label').text('Next Status (None)');
                $('#selected_status_comment_label').text('Next Status (None)');
            }

            if (!leadId || !nextStatusId) {
                $('#DATE').val('');
                $('#FOLLOW_UP_COMMENT').val('');
                return;
            }

            $.ajax({
                url: 'leads.php',
                type: 'GET',
                data: {
                    ajax: 'get_status_data',
                    lead_id: leadId,
                    status_id: nextStatusId
                },
                dataType: 'json',
                success: function(data) {
                    $('#DATE').val(data.date);
                    $('#FOLLOW_UP_COMMENT').val(data.comment);
                },
                error: function() {
                    console.log('Error loading next status data');
                    $('#DATE').val('');
                    $('#FOLLOW_UP_COMMENT').val('');
                }
            });
        }

        function getNextStatusId(currentId) {
            let statusOptions = $('#PK_LEAD_STATUS option');
            let found = false;
            let nextId = null;

            for (let i = 0; i < statusOptions.length; i++) {
                let optionValue = $(statusOptions[i]).val();

                if (found) {
                    nextId = optionValue;
                    break;
                }

                if (optionValue == currentId) {
                    found = true;
                }
            }

            return nextId;
        }

        function getNextStatusText(currentId) {
            let statusOptions = $('#PK_LEAD_STATUS option');
            let found = false;
            let nextText = null;

            for (let i = 0; i < statusOptions.length; i++) {
                let optionValue = $(statusOptions[i]).val();
                let optionText = $(statusOptions[i]).text();

                if (found) {
                    nextText = optionText;
                    break;
                }

                if (optionValue == currentId) {
                    found = true;
                }
            }

            return nextText;
        }

        $(document).ready(function() {
            <?php if (!empty($_GET['id'])): ?>
                let currentId = $('#PK_LEAD_STATUS').val();
                let nextText = getNextStatusText(currentId);
                if (nextText) {
                    $('#selected_status_label').text(nextText);
                    $('#selected_status_comment_label').text(nextText);
                }
                loadStatusData();
            <?php endif; ?>
        });
    </script>
</body>

</html>