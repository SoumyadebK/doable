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
            'COMMENT' => $_POST['FOLLOW_UP_COMMENT'], // Add this field to your database
            'CREATED_BY' => $_SESSION['PK_USER'],
            'CREATED_ON' => date("Y-m-d H:i")
        );
        db_perform('DOA_LEAD_DATE', $LEAD_DATE, 'insert');
    }

    header("location:all_leads.php");
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
    $EMAIL_ID = $res->fields['EMAIL_ID'];
    $PK_LEAD_STATUS = $res->fields['PK_LEAD_STATUS'];
    $OPPORTUNITY_SOURCE = $res->fields['OPPORTUNITY_SOURCE'];
    $DESCRIPTION = $res->fields['DESCRIPTION'];
    $ACTIVE = $res->fields['ACTIVE'];

    // Get the latest lead date if exists
    $date_res = $db->Execute("SELECT DATE FROM `DOA_LEAD_DATE` WHERE PK_LEADS = '$_GET[id]' ORDER BY DATE DESC LIMIT 1");
    if ($date_res->RecordCount() > 0) {
        $DATE = date("m/d/Y", strtotime($date_res->fields['DATE']));
        $COMMENT = $db->Execute("SELECT COMMENT FROM `DOA_LEAD_DATE` WHERE PK_LEADS = '$_GET[id]' ORDER BY DATE DESC LIMIT 1")->fields['COMMENT'];
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
<?php require_once('../includes/header.php'); ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <div class="container-fluid body_content">
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

                                    <!-- First Row: Location, Follow up Date, Status Log -->
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

                                    <!-- Rest of the fields -->
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Phone</label>
                                                <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone Number" value="<?php echo $PHONE ?>">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Email</label>
                                                <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" value="<?= $EMAIL_ID ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Lead Status <span class="text-danger">*</span></label>
                                                <select class="form-control" name="PK_LEAD_STATUS" id="PK_LEAD_STATUS" required>
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

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control" name="DESCRIPTION" rows="2" placeholder="Enter lead description"><?= $DESCRIPTION ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Follow up Date</label>
                                                <input type="text" id="DATE" name="DATE" class="form-control datepicker-normal" placeholder="Select Follow up Date" value="<?php echo $DATE ?>">
                                                <small class="form-text text-muted">Select date for follow-up</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Follow up Comment</label>
                                                <textarea class="form-control" name="FOLLOW_UP_COMMENT" id="FOLLOW_UP_COMMENT" rows="1" placeholder="Enter comment for this follow up"><?= htmlspecialchars($COMMENT) ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Status Log</label>
                                                <div class="status-log-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px; background: #f8f9fa;">
                                                    <?php if (!empty($_GET['id']) && $status_logs && $status_logs->RecordCount() > 0): ?>
                                                        <?php while (!$status_logs->EOF): ?>
                                                            <div class="status-log-item mb-2 pb-2 border-bottom">
                                                                <div class="d-flex justify-content-between">
                                                                    <strong><?= $status_logs->fields['LEAD_STATUS'] ?? 'N/A' ?></strong>
                                                                    <small class="text-muted"><?= date('m/d/Y', strtotime($status_logs->fields['DATE'])) ?></small>
                                                                </div>
                                                                <?php if (!empty($status_logs->fields['COMMENT'])): ?>
                                                                    <p class="mb-1 small"><?= htmlspecialchars($status_logs->fields['COMMENT']) ?></p>
                                                                <?php endif; ?>
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
                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
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
    function createCustomer() {
        let PK_LEADS = $('#PK_LEADS').val();
        let PK_LOCATION = $('#PK_LOCATION').val();
        let FIRST_NAME = $('#FIRST_NAME').val();
        let LAST_NAME = $('#LAST_NAME').val();
        let PHONE = $('#PHONE').val();
        let EMAIL_ID = $('#EMAIL_ID').val();

        if (!PK_LOCATION || !FIRST_NAME) {
            alert('Please select location and enter first name before creating customer');
            return;
        }

        window.location.href = `customer.php?PK_LOCATION=${encodeURIComponent(PK_LOCATION)}&FIRST_NAME=${encodeURIComponent(FIRST_NAME)}&LAST_NAME=${encodeURIComponent(LAST_NAME)}&PHONE=${encodeURIComponent(PHONE)}&EMAIL_ID=${encodeURIComponent(EMAIL_ID)}&PK_LEADS=${PK_LEADS}`;
    }

    $(document).ready(function() {
        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
            autoclose: true,
            todayHighlight: true
        });
    });
</script>

<style>
    .status-log-container {
        max-height: 200px;
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
</style>

</html>