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

// Capture filter parameters to preserve them when going back
$filter_start_date = isset($_GET['filter_start_date']) ? $_GET['filter_start_date'] : (isset($_POST['filter_start_date']) ? $_POST['filter_start_date'] : '');
$filter_end_date = isset($_GET['filter_end_date']) ? $_GET['filter_end_date'] : (isset($_POST['filter_end_date']) ? $_POST['filter_end_date'] : '');
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : (isset($_POST['filter_status']) ? $_POST['filter_status'] : '');
$filter_search = isset($_GET['filter_search']) ? $_GET['filter_search'] : (isset($_POST['filter_search']) ? $_POST['filter_search'] : '');
$filter_page = isset($_GET['filter_page']) ? $_GET['filter_page'] : (isset($_POST['filter_page']) ? $_POST['filter_page'] : '');

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
        // Insert new lead status record
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

    // Preserve the date filter and other parameters when redirecting back
    $redirect_url = "all_leads.php";
    $preserve_params = array();

    // Check for date range filters from POST or GET
    if (!empty($_POST['filter_start_date'])) {
        $preserve_params['start_date'] = $_POST['filter_start_date'];
    } elseif (!empty($_GET['filter_start_date'])) {
        $preserve_params['start_date'] = $_GET['filter_start_date'];
    }

    if (!empty($_POST['filter_end_date'])) {
        $preserve_params['end_date'] = $_POST['filter_end_date'];
    } elseif (!empty($_GET['filter_end_date'])) {
        $preserve_params['end_date'] = $_GET['filter_end_date'];
    }

    // Preserve status filter
    if (!empty($_POST['filter_status'])) {
        $preserve_params['status'] = $_POST['filter_status'];
    } elseif (!empty($_GET['filter_status'])) {
        $preserve_params['status'] = $_GET['filter_status'];
    }

    // Preserve search text
    if (!empty($_POST['filter_search'])) {
        $preserve_params['search_text'] = $_POST['filter_search'];
    } elseif (!empty($_GET['filter_search'])) {
        $preserve_params['search_text'] = $_GET['filter_search'];
    }

    // Preserve page number
    if (!empty($_POST['filter_page'])) {
        $preserve_params['page'] = $_POST['filter_page'];
    } elseif (!empty($_GET['filter_page'])) {
        $preserve_params['page'] = $_GET['filter_page'];
    }

    if (!empty($preserve_params)) {
        $redirect_url .= "?" . http_build_query($preserve_params);
    }

    header("location:" . $redirect_url);
    exit;
}

function getUserIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipArray = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipArray[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
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
    // Get lead data
    $res = $db->Execute("SELECT * FROM `DOA_LEADS` WHERE PK_LEADS = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_leads.php");
        exit;
    }

    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $FIRST_NAME = $res->fields['FIRST_NAME'];
    $LAST_NAME = $res->fields['LAST_NAME'];
    $PHONE = $res->fields['PHONE'];

    function formatPhone($PHONE)
    {
        $PHONE = preg_replace('/\D/', '', $PHONE);

        // Remove country code if 11 digits starting with 1
        if (strlen($PHONE) == 11 && substr($PHONE, 0, 1) == '1') {
            $PHONE = substr($PHONE, 1);
        }

        if (strlen($PHONE) == 10) {
            return '(' . substr($PHONE, 0, 3) . ') '
                . substr($PHONE, 3, 3) . '-'
                . substr($PHONE, 6);
        }

        return $PHONE;
    }

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

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 0px !important;">

            <div class="container-fluid body_content" style="margin-top: 0px;">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item"><a href="all_leads.php">All Leads</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form class="form-material form-horizontal m-t-30" name="form1" id="form1" action="" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="PK_LEADS" id="PK_LEADS" value="<?= $_GET['id'] ?? '' ?>" />
                                    <!-- Hidden fields to preserve filters when saving -->
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

                                    <!-- First Row: Location -->
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Location <span class="text-danger">*</span></label>
                                                <select class="form-control" name="PK_LOCATION" id="PK_LOCATION" required>
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
                                        </div>
                                    </div>

                                    <!-- Name Row -->
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                                <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" value="<?= $FIRST_NAME ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name" value="<?= $LAST_NAME ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact Info Row -->
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Phone</label>
                                                <input type="text" id="PHONE" name="PHONE" class="form-control format_phone_number" placeholder="Enter Phone Number" value="<?php echo formatPhone($PHONE) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Email</label>
                                                <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" value="<?= $EMAIL_ID ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Lead Status and Source Row -->
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Lead Status <span class="text-danger">*</span></label>
                                                <select class="form-control" name="PK_LEAD_STATUS" id="PK_LEAD_STATUS" required onchange="loadStatusData()">
                                                    <?php
                                                    $lead_statuses = $db->Execute("SELECT * FROM `DOA_LEAD_STATUS` WHERE ACTIVE = 1 AND `PK_ACCOUNT_MASTER` = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DISPLAY_ORDER ASC");
                                                    while (!$lead_statuses->EOF) { ?>
                                                        <option value="<?php echo $lead_statuses->fields['PK_LEAD_STATUS']; ?>" <?= ($lead_statuses->fields['PK_LEAD_STATUS'] == $PK_LEAD_STATUS) ? 'selected' : '' ?>><?= $lead_statuses->fields['LEAD_STATUS'] ?></option>
                                                    <?php $lead_statuses->MoveNext();
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Opportunity Source</label>
                                                <input type="text" id="OPPORTUNITY_SOURCE" name="OPPORTUNITY_SOURCE" class="form-control" placeholder="Enter Opportunity Source" value="<?php echo $OPPORTUNITY_SOURCE ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Description Row -->
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Remarks</label>
                                                <textarea class="form-control" id="DESCRIPTION" name="DESCRIPTION" rows="2" placeholder="Enter lead description"><?= $DESCRIPTION ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Follow-up Info Row (These will update when status changes) -->
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Follow up Date for <span id="selected_status_label">Selected Status</span></label>
                                                <input type="text" id="DATE" name="DATE" class="form-control datepicker-normal" placeholder="Select Follow up Date" value="<?php echo $DATE ?>">
                                                <small class="form-text text-muted">Select date for follow-up</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Comment for <span id="selected_status_comment_label">Selected Status</span></label>
                                                <textarea class="form-control" name="FOLLOW_UP_COMMENT" id="FOLLOW_UP_COMMENT" rows="1" placeholder="Enter comment for this follow up"><?= htmlspecialchars($COMMENT) ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Log (All History) -->
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label class="form-label">Status Log</label>
                                                <div class="status-log-container" style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px; background: #f8f9fa;">
                                                    <?php if (!empty($_GET['id']) && $status_logs && $status_logs->RecordCount() > 0): ?>
                                                        <?php while (!$status_logs->EOF): ?>
                                                            <div class="status-log-item mb-2 pb-2 border-bottom">
                                                                <div class="d-flex justify-content-between">
                                                                    <strong><?= $status_logs->fields['LEAD_STATUS'] ?? 'N/A' ?></strong>
                                                                    <small class="text-muted"><?= !empty($status_logs->fields['DATE']) ? date('m/d/Y', strtotime($status_logs->fields['DATE'])) : 'No date' ?></small>
                                                                </div>
                                                                <?php if (!empty($status_logs->fields['COMMENT'])): ?>
                                                                    <p class="mb-1 small"><?= htmlspecialchars($status_logs->fields['COMMENT']) ?></p>
                                                                <?php endif; ?>
                                                                <small class="text-muted">Added on <?= date('m/d/Y H:i', strtotime($status_logs->fields['CREATED_ON'])) ?></small>
                                                            </div>
                                                        <?php
                                                            $status_logs->MoveNext();
                                                        endwhile; ?>
                                                    <?php else: ?>
                                                        <p class="text-muted mb-0">No status logs available</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($_GET['id'])) { ?>
                                        <div class="row mt-3">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label d-block">Active</label>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="ACTIVE" id="ACTIVE_YES" value="1" <? if ($ACTIVE == 1) echo 'checked="checked"'; ?>>
                                                        <label class="form-check-label" for="ACTIVE_YES">Yes</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="ACTIVE" id="ACTIVE_NO" value="0" <? if ($ACTIVE == 0) echo 'checked="checked"'; ?>>
                                                        <label class="form-check-label" for="ACTIVE_NO">No</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <? } ?>

                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Save</button>
                                            <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_leads.php'">Cancel</button>
                                            <a href="javascript:;" onclick="createCustomer()" class="btn btn-success waves-effect waves-light m-r-10 text-white">Create Customer</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
</body>

<script>
    // Function to format phone number as user types
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

    // Store the original lead status when the page loads
    var originalLeadStatus = '<?= !empty($_GET['id']) ? $PK_LEAD_STATUS : '' ?>';

    function createCustomer() {
        let PK_LEADS = $('#PK_LEADS').val();
        let PK_LOCATION = $('#PK_LOCATION').val();
        let FIRST_NAME = $('#FIRST_NAME').val();
        let LAST_NAME = $('#LAST_NAME').val();
        let PHONE = $('#PHONE').val();
        let EMAIL_ID = $('#EMAIL_ID').val();
        let NOTES = $('#DESCRIPTION').val();

        if (!PK_LOCATION || !FIRST_NAME) {
            alert('Please select location and enter first name before creating customer');
            return;
        }

        window.location.href = `../admin/customer.php?PK_LOCATION=${encodeURIComponent(PK_LOCATION)}&FIRST_NAME=${encodeURIComponent(FIRST_NAME)}&LAST_NAME=${encodeURIComponent(LAST_NAME)}&PHONE=${encodeURIComponent(PHONE)}&EMAIL_ID=${encodeURIComponent(EMAIL_ID)}&PK_LEADS=${PK_LEADS}&NOTES=${encodeURIComponent(NOTES)}`;
    }

    function loadStatusData() {
        let leadId = $('#PK_LEADS').val();
        let currentStatusId = $('#PK_LEAD_STATUS').val();
        let currentStatusText = $('#PK_LEAD_STATUS option:selected').text();

        // Get the next status ID from the dropdown
        let nextStatusId = getNextStatusId(currentStatusId);
        let nextStatusText = getNextStatusText(currentStatusId);

        // Update the labels to show the NEXT status name
        if (nextStatusText) {
            $('#selected_status_label').text(nextStatusText);
            $('#selected_status_comment_label').text(nextStatusText);
        } else {
            $('#selected_status_label').text('Next Status (None)');
            $('#selected_status_comment_label').text('Next Status (None)');
        }

        if (!leadId || !nextStatusId) {
            // If no lead ID (new lead) or no next status, clear the fields
            $('#DATE').val('');
            $('#FOLLOW_UP_COMMENT').val('');
            return;
        }

        // Load the follow-up data for the NEXT status
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
        // Get all status options
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
        // Get all status options
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
        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
            autoclose: true,
            todayHighlight: true
        });

        // Initialize on page load - show next status data
        <?php if (!empty($_GET['id'])): ?>
            // Get next status text and data
            let currentId = $('#PK_LEAD_STATUS').val();
            let nextText = getNextStatusText(currentId);
            if (nextText) {
                $('#selected_status_label').text(nextText);
                $('#selected_status_comment_label').text(nextText);
            }

            // Load the next status data
            loadStatusData();
        <?php endif; ?>
    });
</script>

<style>
    .status-log-container {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 10px;
        background: #f8f9fa;
    }

    .status-log-item:last-child {
        border-bottom: none !important;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }

    .status-log-item p {
        font-size: 0.9rem;
        margin-top: 3px;
    }

    #selected_status_label,
    #selected_status_comment_label {
        font-weight: bold;
        color: #39b54a;
    }
</style>

</html>