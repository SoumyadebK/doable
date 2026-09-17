<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Campaign";
else
    $title = "Edit Campaign";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// ---------------------------------------------------------------
// Determine if the campaign's location is accessible to this user
// ---------------------------------------------------------------
$campaign_location_id   = 0;
$campaign_location_name = '';
$location_access_denied = false;
$is_edit_mode           = !empty($_GET['id']);

// Load the campaign's own location up-front (before rendering the dropdown)
if ($is_edit_mode) {
    $loc_check = $db_account->Execute(
        "SELECT DOA_MARKET_CAMPAIGN.PK_LOCATION, DOA_LOCATION.LOCATION_NAME
         FROM DOA_MARKET_CAMPAIGN
         LEFT JOIN $master_database.DOA_LOCATION
                ON DOA_MARKET_CAMPAIGN.PK_LOCATION = DOA_LOCATION.PK_LOCATION
         WHERE DOA_MARKET_CAMPAIGN.PK_MARKET_CAMPAIGN = " . intval($_GET['id']) . "
           AND DOA_MARKET_CAMPAIGN.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER'])
    );

    if ($loc_check && $loc_check->RecordCount() > 0) {
        $campaign_location_id   = (int)$loc_check->fields['PK_LOCATION'];
        $campaign_location_name = $loc_check->fields['LOCATION_NAME'];

        // Is that location in the user's allowed list?
        $allowed_ids = array_filter(array_map('intval', explode(',', $_SESSION['DEFAULT_LOCATION_ID'])));
        if (!in_array($campaign_location_id, $allowed_ids, true)) {
            $location_access_denied = true;
        }
    }
}

// Get selected tag values for edit mode
$selected_tag = array();
if (!empty($_GET['id'])) {
    $tag_res = $db_account->Execute("SELECT TAGS FROM DOA_MARKET_CAMPAIGN WHERE PK_MARKET_CAMPAIGN = " . intval($_GET['id']));
    if ($tag_res->RecordCount() > 0) {
        $tags_str = $tag_res->fields['TAGS'];
        if (!empty($tags_str)) {
            $selected_tag = explode(',', $tags_str);
        }
    }
}

// Get all tags for display
$all_tags = array();
$tag_res = $db_account->Execute("SELECT PK_TAG, TAG_NAME FROM DOA_TAG WHERE ACTIVE = 1 ORDER BY TAG_NAME");
while (!$tag_res->EOF) {
    $all_tags[] = array(
        'id'   => $tag_res->fields['PK_TAG'],
        'name' => $tag_res->fields['TAG_NAME']
    );
    $tag_res->MoveNext();
}

// Get all lead statuses for display
$all_lead_statuses = array();
$status_res = $db->Execute("SELECT PK_LEAD_STATUS, LEAD_STATUS FROM DOA_LEAD_STATUS WHERE PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " AND ACTIVE = 1 ORDER BY LEAD_STATUS");
while (!$status_res->EOF) {
    $all_lead_statuses[] = array(
        'id'   => $status_res->fields['PK_LEAD_STATUS'],
        'name' => $status_res->fields['LEAD_STATUS']
    );
    $status_res->MoveNext();
}

// Get location timezone
function getLocationTimezone($location_id)
{
    global $db;
    $tz_res = $db->Execute("SELECT TIMEZONE FROM DOA_LOCATION LEFT JOIN DOA_TIMEZONE ON DOA_LOCATION.PK_TIMEZONE = DOA_TIMEZONE.PK_TIMEZONE WHERE DOA_LOCATION.PK_LOCATION = " . intval($location_id));
    if ($tz_res && $tz_res->RecordCount() > 0) {
        return $tz_res->fields['TIMEZONE'];
    }
    return 'America/New_York';
}

// ---------------------------------------------------------------
// Handle POST — block the save entirely if location access denied
// ---------------------------------------------------------------
if (!empty($_POST)) {

    // Safety: refuse to save if the user can't access this campaign's location
    if ($location_access_denied) {
        $save_error = "You do not have access to this campaign's location. The form is read-only.";
    } else {
        $CAMPAIGN_DATA = array();
        $CAMPAIGN_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];

        // Only allow changing PK_LOCATION if the user actually changed it.
        // In edit mode, keep the original location unless the user deliberately picked a new one.
        if ($is_edit_mode) {
            $submitted_location = isset($_POST['PK_LOCATION']) ? (int)$_POST['PK_LOCATION'] : 0;
            if ($submitted_location === $campaign_location_id) {
                // No change — keep original
                $CAMPAIGN_DATA['PK_LOCATION'] = $campaign_location_id;
            } else {
                // User deliberately changed it — validate it's within their allowed list
                $allowed_ids = array_filter(array_map('intval', explode(',', $_SESSION['DEFAULT_LOCATION_ID'])));
                if (in_array($submitted_location, $allowed_ids, true)) {
                    $CAMPAIGN_DATA['PK_LOCATION'] = $submitted_location;
                } else {
                    $CAMPAIGN_DATA['PK_LOCATION'] = $campaign_location_id; // refuse invalid change
                }
            }
        } else {
            $CAMPAIGN_DATA['PK_LOCATION'] = (int)$_POST['PK_LOCATION'];
        }

        $CAMPAIGN_DATA['CAMPAIGN_NAME'] = $_POST['TEMPLATE_NAME'];
        $CAMPAIGN_DATA['SUBJECT']       = $_POST['SUBJECT'];

        if (!empty($_POST['OPERATION']) && is_array($_POST['OPERATION'])) {
            $CAMPAIGN_DATA['OPERATION'] = implode(',', $_POST['OPERATION']);
        } else {
            $CAMPAIGN_DATA['OPERATION'] = '';
        }

        $CAMPAIGN_DATA['REMINDER_TYPE'] = implode(',', $_POST['REMINDER_TYPE']);
        $CAMPAIGN_DATA['CONTENT']       = $_POST['CONTENT'];

        if (!empty($_POST['PK_USER_TAG']) && is_array($_POST['PK_USER_TAG'])) {
            $CAMPAIGN_DATA['TAGS'] = implode(',', $_POST['PK_USER_TAG']);
        } else {
            $CAMPAIGN_DATA['TAGS'] = '';
        }

        if (!empty($_POST['PK_LEAD_STATUS']) && is_array($_POST['PK_LEAD_STATUS'])) {
            $CAMPAIGN_DATA['LEADS'] = implode(',', $_POST['PK_LEAD_STATUS']);
        } else {
            $CAMPAIGN_DATA['LEADS'] = '';
        }

        if (!empty($_POST['SCHEDULE_DATE']) && !empty($_POST['SCHEDULE_TIME'])) {
            $schedule_datetime = $_POST['SCHEDULE_DATE'] . ' ' . $_POST['SCHEDULE_TIME'];
            $CAMPAIGN_DATA['SCHEDULE_DATETIME'] = $schedule_datetime;
            if (!empty($CAMPAIGN_DATA['PK_LOCATION'])) {
                $CAMPAIGN_DATA['TIMEZONE'] = getLocationTimezone($CAMPAIGN_DATA['PK_LOCATION']);
            }
        } else {
            $CAMPAIGN_DATA['SCHEDULE_DATETIME'] = null;
            $CAMPAIGN_DATA['TIMEZONE']          = null;
        }

        if (isset($_POST['ACTIVE'])) {
            $CAMPAIGN_DATA['ACTIVE'] = $_POST['ACTIVE'];
        } else {
            $CAMPAIGN_DATA['ACTIVE'] = 1;
        }

        if (empty($_GET['id'])) {
            $CAMPAIGN_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $CAMPAIGN_DATA['CREATED_ON'] = date("Y-m-d H:i:s");
            $CAMPAIGN_DATA['EDITED_BY']  = 0;
            $CAMPAIGN_DATA['EDITED_ON']  = '0000-00-00 00:00:00';
            db_perform_account('DOA_MARKET_CAMPAIGN', $CAMPAIGN_DATA, 'insert');
            $new_id = $db_account->Insert_ID();   // ADOdb returns the last inserted ID
            header("location:all_marketings.php?saved=" . intval($new_id) . "&saved_name=" . urlencode($CAMPAIGN_DATA['CAMPAIGN_NAME']));
            exit;
        } else {
            $CAMPAIGN_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
            $CAMPAIGN_DATA['EDITED_ON'] = date("Y-m-d H:i:s");
            db_perform_account('DOA_MARKET_CAMPAIGN', $CAMPAIGN_DATA, 'update', " PK_MARKET_CAMPAIGN = " . intval($_GET['id']));
            header("location:all_marketings.php?saved=" . intval($_GET['id']) . "&saved_name=" . urlencode($CAMPAIGN_DATA['CAMPAIGN_NAME']));
            exit;
        }
    }
}

if (empty($_GET['id'])) {
    $TEMPLATE_NAME = '';
    $PK_LOCATION   = '';
    $SUBJECT       = '';
    $OPERATION     = array();
    $CONTENT       = '';
    $ACTIVE        = '';
    $REMINDER_TYPE = array('email');
    $selected_lead_statuses = array();
    $SCHEDULE_DATETIME = '';
    $SCHEDULE_DATE     = '';
    $SCHEDULE_TIME     = '';
    $TIMEZONE          = '';
} else {
    $res = $db_account->Execute("SELECT * FROM DOA_MARKET_CAMPAIGN WHERE PK_MARKET_CAMPAIGN = " . intval($_GET['id']));
    if ($res->RecordCount() == 0) {
        header("location:all_marketings.php");
        exit;
    }
    $TEMPLATE_NAME = $res->fields['CAMPAIGN_NAME'];
    $PK_LOCATION   = $res->fields['PK_LOCATION'];
    $SUBJECT       = $res->fields['SUBJECT'];
    $OPERATION     = !empty($res->fields['OPERATION']) ? explode(',', $res->fields['OPERATION']) : array();
    $REMINDER_TYPE = !empty($res->fields['REMINDER_TYPE']) ? explode(',', $res->fields['REMINDER_TYPE']) : array('email');
    $CONTENT       = $res->fields['CONTENT'];
    $ACTIVE        = $res->fields['ACTIVE'];
    $SCHEDULE_DATETIME = $res->fields['SCHEDULE_DATETIME'];
    $TIMEZONE          = $res->fields['TIMEZONE'];

    if (!empty($SCHEDULE_DATETIME) && $SCHEDULE_DATETIME != '0000-00-00 00:00:00') {
        $datetime_parts = explode(' ', $SCHEDULE_DATETIME);
        $SCHEDULE_DATE  = $datetime_parts[0];
        $SCHEDULE_TIME  = $datetime_parts[1];
    } else {
        $SCHEDULE_DATE = '';
        $SCHEDULE_TIME = '';
    }

    $selected_tag = !empty($res->fields['TAGS']) ? explode(',', $res->fields['TAGS']) : array();
    $selected_lead_statuses = !empty($res->fields['LEADS']) ? explode(',', $res->fields['LEADS']) : array();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="assets/css/setup-styles.css" rel="stylesheet">

<style>
    :root {
        --primary-color: #39B54A;
        --primary-light: #5DCB6E;
        --primary-dark: #2D8F3B;
        --primary-rgb: 57, 181, 74;
        --success-color: #39B54A;
        --warning-color: #F59E0B;
        --danger-color: #EF4444;
        --gray-50: #F9FAFB;
        --gray-100: #F3F4F6;
        --gray-200: #E5E7EB;
        --gray-300: #D1D5DB;
        --gray-400: #9CA3AF;
        --gray-500: #6B7280;
        --gray-600: #4B5563;
        --gray-700: #374151;
        --gray-800: #1F2937;
        --gray-900: #111827;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --radius: 12px;
        --radius-sm: 8px;
        --radius-lg: 16px;
        --radius-pill: 50px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        background: var(--gray-50);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .page-wrapper {
        padding-top: 0px !important;
        background: var(--gray-50);
    }

    .card-modern {
        background: #ffffff;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
        overflow: hidden;
        transition: box-shadow 0.2s ease;
    }

    .card-modern:hover {
        box-shadow: var(--shadow-md);
    }

    .card-modern .card-header {
        padding: 20px 24px;
        background: var(--gray-50);
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-modern .card-header h5 {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
    }

    .card-modern .card-header h5 i {
        color: var(--primary-color);
        margin-right: 8px;
    }

    .card-modern .card-body {
        padding: 28px 32px;
    }

    @media (max-width: 768px) {
        .card-modern .card-body {
            padding: 20px;
        }

        .container-fluid {
            padding: 16px !important;
        }
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px 24px;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .form-group-modern {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group-modern .form-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--gray-700);
        letter-spacing: 0.01em;
    }

    .form-group-modern .form-label .required {
        color: var(--danger-color);
        margin-left: 2px;
    }

    .form-group-modern .form-label .helper {
        font-weight: 400;
        color: var(--gray-400);
        font-size: 12px;
    }

    .form-control-modern {
        width: 100%;
        padding: 10px 14px;
        font-size: 14px;
        color: var(--gray-800);
        background: #fff;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        transition: all 0.2s ease;
        outline: none;
        font-family: inherit;
    }

    .form-control-modern:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .form-control-modern:hover {
        border-color: var(--gray-300);
    }

    .form-control-modern::placeholder {
        color: var(--gray-400);
        font-size: 13px;
    }

    .form-control-modern.is-invalid {
        border-color: var(--danger-color);
    }

    .form-control-modern.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    .form-control-modern:disabled {
        background: var(--gray-100);
        color: var(--gray-500);
        cursor: not-allowed;
    }

    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    .radio-group-modern {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        padding-top: 4px;
    }

    .radio-group-modern .radio-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
    }

    .radio-group-modern .radio-item input[type="radio"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        flex-shrink: 0;
    }

    .checkbox-group-modern {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        padding-top: 4px;
    }

    .checkbox-group-modern .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
    }

    .checkbox-group-modern .checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        flex-shrink: 0;
        accent-color: var(--primary-color);
    }

    .btn-modern {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 28px;
        font-size: 14px;
        font-weight: 500;
        border: none;
        border-radius: var(--radius-pill);
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        font-family: inherit;
        line-height: 1.5;
    }

    .btn-modern-primary {
        background: var(--primary-color);
        color: #fff;
    }

    .btn-modern-primary:hover {
        background: var(--primary-dark);
        box-shadow: var(--shadow-md);
        transform: translateY(-1px);
        color: #fff;
    }

    .btn-modern-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .btn-modern-secondary {
        background: var(--gray-100);
        color: var(--gray-700);
    }

    .btn-modern-secondary:hover {
        background: var(--gray-200);
        color: var(--gray-800);
    }

    .btn-modern-success {
        background: var(--success-color);
        color: #fff;
    }

    .btn-modern-success:hover {
        background: var(--primary-dark);
        box-shadow: var(--shadow-md);
        transform: translateY(-1px);
        color: #fff;
    }

    .btn-modern-danger {
        background: var(--danger-color);
        color: #fff;
    }

    .btn-modern-danger:hover {
        background: #DC2626;
        color: #fff;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid var(--gray-200);
        flex-wrap: wrap;
    }

    @media (max-width: 640px) {
        .form-actions {
            flex-direction: column;
        }

        .form-actions .btn-modern {
            width: 100%;
            justify-content: center;
        }
    }

    .full-width {
        grid-column: 1 / -1;
    }

    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 500;
    }

    .status-indicator.active {
        background: #D1FAE5;
        color: #065F46;
    }

    .status-indicator.inactive {
        background: #FEE2E2;
        color: #991B1B;
    }

    .status-indicator i {
        font-size: 8px;
    }

    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
    }

    .form-helper.error {
        color: var(--danger-color);
        font-weight: 500;
    }

    .variable-badge {
        background-color: #eef2ff;
        border-radius: 20px;
        padding: 0.2rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 500;
        display: inline-block;
        margin: 0 2px;
        color: #1e40af;
        cursor: default;
        border: 1px solid #c7d2fe;
        user-select: none;
    }

    .variable-badge:hover {
        background-color: #e0e7ff;
    }

    .btn-variable-token {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 40px;
        font-size: 0.7rem;
        padding: 0.25rem 0.9rem;
        transition: all 0.2s ease;
        cursor: pointer;
        font-weight: 500;
        color: var(--gray-700);
    }

    .btn-variable-token:hover {
        background: #f1f5f9;
        border-color: var(--primary-color);
        transform: translateY(-1px);
        box-shadow: var(--shadow-sm);
        color: var(--gray-800);
    }

    .btn-variable-token:active {
        transform: translateY(0px);
    }

    .variables-section {
        grid-column: 1 / -1;
        padding: 8px 0 4px 0;
        margin-top: 8px;
    }

    .variables-section .text-muted {
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-500) !important;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
    }

    .variables-section .d-flex {
        gap: 6px;
        flex-wrap: wrap;
    }

    .conditional-field {
        display: none !important;
    }

    .conditional-field.visible {
        display: block !important;
    }

    .checkbox-container {
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        padding: 12px 16px;
        max-height: 200px;
        overflow-y: auto;
        background: #fff;
        transition: border-color 0.2s ease;
    }

    .checkbox-container:hover {
        border-color: var(--gray-300);
    }

    .checkbox-container:focus-within {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .checkbox-container .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 4px;
        border-radius: 4px;
        transition: background 0.15s ease;
        cursor: pointer;
    }

    .checkbox-container .checkbox-item:hover {
        background: var(--gray-50);
    }

    .checkbox-container .checkbox-item .form-check-input {
        width: 18px;
        height: 18px;
        cursor: pointer;
        flex-shrink: 0;
        margin: 0;
        accent-color: var(--primary-color);
    }

    .checkbox-container .checkbox-item label {
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
        margin: 0;
        user-select: none;
    }

    .checkbox-container .checkbox-item.select-all-item {
        border-bottom: 1px solid var(--gray-200);
        padding-bottom: 10px;
        margin-bottom: 6px;
    }

    .checkbox-container .checkbox-item.select-all-item label {
        font-weight: 600;
        color: var(--gray-800);
    }

    .selected-count {
        font-size: 13px;
        color: var(--gray-500);
        margin-top: 6px;
        font-weight: 500;
    }

    .selected-count span {
        color: var(--primary-color);
        font-weight: 600;
    }

    .checkbox-container::-webkit-scrollbar {
        width: 6px;
    }

    .checkbox-container::-webkit-scrollbar-track {
        background: var(--gray-100);
        border-radius: 3px;
    }

    .checkbox-container::-webkit-scrollbar-thumb {
        background: var(--gray-300);
        border-radius: 3px;
    }

    .checkbox-container::-webkit-scrollbar-thumb:hover {
        background: var(--gray-400);
    }

    .subject-optional {
        font-size: 12px;
        color: var(--gray-400);
        font-weight: 400;
    }

    .subject-optional.required-text {
        color: var(--danger-color);
    }

    .schedule-info {
        background: var(--gray-50);
        border-radius: var(--radius-sm);
        padding: 12px 16px;
        border: 1px solid var(--gray-200);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .schedule-info i {
        color: var(--primary-color);
        font-size: 1.2rem;
    }

    .schedule-info .timezone-badge {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: 20px;
        padding: 2px 12px;
        font-size: 12px;
        font-weight: 500;
        color: var(--gray-600);
    }

    .datetime-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
    }

    .schedule-toggle {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }

    .schedule-toggle .form-check {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .schedule-toggle .form-check-input {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: var(--primary-color);
    }

    .schedule-toggle .form-check-label {
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
    }

    .schedule-fields {
        display: none;
        padding: 16px;
        background: var(--gray-50);
        border-radius: var(--radius-sm);
        border: 1px solid var(--gray-200);
    }

    .schedule-fields.visible {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    @media (max-width: 768px) {
        .schedule-fields.visible {
            grid-template-columns: 1fr;
        }
    }

    .schedule-fields .form-control-modern[type="date"],
    .schedule-fields .form-control-modern[type="time"] {
        padding: 10px 14px;
    }

    .schedule-fields .form-control-modern[type="time"] {
        min-width: 140px;
    }

    .audience-checkbox-container {
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        padding: 12px 16px;
        background: #fff;
        transition: border-color 0.2s ease;
    }

    .audience-checkbox-container:hover {
        border-color: var(--gray-300);
    }

    .audience-checkbox-container:focus-within {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .audience-checkbox-container .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 4px;
        border-radius: 4px;
        transition: background 0.15s ease;
        cursor: pointer;
    }

    .audience-checkbox-container .checkbox-item:hover {
        background: var(--gray-50);
    }

    .audience-checkbox-container .checkbox-item .form-check-input {
        width: 18px;
        height: 18px;
        cursor: pointer;
        flex-shrink: 0;
        margin: 0;
        accent-color: var(--primary-color);
    }

    .audience-checkbox-container .checkbox-item label {
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
        margin: 0;
        user-select: none;
    }

    .content-editable {
        width: 100%;
        min-height: 250px;
        padding: 12px 16px;
        font-size: 14px;
        color: var(--gray-800);
        background: #fff;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        transition: all 0.2s ease;
        outline: none;
        font-family: inherit;
        line-height: 1.8;
        overflow-y: auto;
    }

    .content-editable:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .content-editable:hover {
        border-color: var(--gray-300);
    }

    .content-editable.is-invalid {
        border-color: var(--danger-color);
    }

    .content-editable.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    .content-editable .variable-badge {
        background-color: #eef2ff;
        border-radius: 20px;
        padding: 0.15rem 0.6rem;
        font-size: 0.8rem;
        font-weight: 500;
        display: inline-block;
        margin: 0 2px;
        color: #1e40af;
        border: 1px solid #c7d2fe;
        cursor: default;
        user-select: none;
    }

    .content-editable .variable-badge:hover {
        background-color: #e0e7ff;
    }

    /* Read-only banner */
    .readonly-banner {
        background: #FEF3C7;
        border: 1px solid #FCD34D;
        color: #92400E;
        border-radius: var(--radius-sm);
        padding: 14px 18px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 20px;
    }

    .readonly-banner i {
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    /* Location lock badge */
    .location-locked-note {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--gray-500);
        margin-top: 4px;
    }

    .location-locked-note i {
        color: var(--gray-400);
    }

    /* Save-attempt errors block */
    .save-errors {
        background: #FEE2E2;
        border: 1px solid #FCA5A5;
        color: #991B1B;
        border-radius: var(--radius-sm);
        padding: 12px 18px;
        margin-bottom: 20px;
        font-size: 13px;
    }

    .save-errors strong {
        display: block;
        margin-bottom: 6px;
    }

    .save-errors ul {
        margin: 0;
        padding-left: 20px;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">

                <div class="row g-4">
                    <div class="col-12 col-md-4 col-xl-2">
                        <?php include 'layout/setup_sidebar.php'; ?>
                    </div>

                    <div class="col-12 col-md-8 col-xl-10">
                        <div class="card-modern">
                            <div class="card-header">
                                <h5>
                                    <i class="bi bi-megaphone"></i>
                                    <?= !empty($_GET['id']) ? 'Edit Campaign' : 'Create New Campaign' ?>
                                </h5>
                                <?php if (!empty($_GET['id'])): ?>
                                    <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                        <i class="fas fa-circle"></i>
                                        <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">

                                <?php if ($location_access_denied): ?>
                                    <div class="readonly-banner">
                                        <i class="bi bi-shield-lock-fill"></i>
                                        <div>
                                            <strong>Read-only campaign</strong>
                                            This campaign belongs to <strong><?= htmlspecialchars($campaign_location_name ?: 'an unknown location') ?></strong>,
                                            which is not part of your assigned locations.
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data" id="campaignForm" novalidate>

                                    <div class="form-grid">
                                        <!-- Campaign Name -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Campaign Name <span class="required">*</span></label>
                                            <input type="text" class="form-control-modern" id="TEMPLATE_NAME" name="TEMPLATE_NAME" placeholder="Enter campaign name" value="<?php echo htmlspecialchars($TEMPLATE_NAME) ?>" required <?= $location_access_denied ? 'disabled' : '' ?>>
                                            <div class="form-helper" id="TEMPLATE_NAME_error">A unique name to identify this campaign</div>
                                        </div>

                                        <!-- Location -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Location <span class="required">*</span></label>
                                            <?php
                                            // Rebuild the dropdown from the campaign's own location first,
                                            // then add any other locations the user has access to.
                                            $allowed_ids = array_filter(array_map('intval', explode(',', $_SESSION['DEFAULT_LOCATION_ID'])));

                                            // Always include the campaign's own location (even if user can't access it,
                                            // so the true value is shown rather than silently replaced).
                                            $ids_for_dropdown = $allowed_ids;
                                            if ($campaign_location_id > 0 && !in_array($campaign_location_id, $ids_for_dropdown, true)) {
                                                $ids_for_dropdown[] = $campaign_location_id;
                                            }

                                            $dropdown_locations = array();
                                            if (!empty($ids_for_dropdown)) {
                                                $ids_str = implode(',', array_map('intval', $ids_for_dropdown));
                                                $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME, ACTIVE
                                                                     FROM DOA_LOCATION
                                                                     WHERE PK_LOCATION IN ($ids_str)
                                                                       AND PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']));
                                                while (!$row->EOF) {
                                                    $dropdown_locations[] = array(
                                                        'id'     => (int)$row->fields['PK_LOCATION'],
                                                        'name'   => $row->fields['LOCATION_NAME'],
                                                        'active' => (int)$row->fields['ACTIVE']
                                                    );
                                                    $row->MoveNext();
                                                }
                                                // Sort so the campaign's own location appears first
                                                usort($dropdown_locations, function ($a, $b) use ($campaign_location_id) {
                                                    if ($a['id'] === $campaign_location_id) return -1;
                                                    if ($b['id'] === $campaign_location_id) return 1;
                                                    return strcasecmp($a['name'], $b['name']);
                                                });
                                            }
                                            ?>
                                            <select class="form-control-modern PK_LOCATION" name="PK_LOCATION" id="PK_LOCATION" required <?= $location_access_denied ? 'disabled' : '' ?>>
                                                <?php if (empty($dropdown_locations)): ?>
                                                    <option value="">No locations available</option>
                                                <?php else: ?>
                                                    <option value="">Select Location</option>
                                                    <?php foreach ($dropdown_locations as $loc): ?>
                                                        <option value="<?= $loc['id'] ?>" <?= ($PK_LOCATION == $loc['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($loc['name']) ?><?= (!$loc['active']) ? ' (Inactive)' : '' ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                            <?php if ($location_access_denied): ?>
                                                <div class="location-locked-note">
                                                    <i class="bi bi-lock-fill"></i>
                                                    Location is locked because you don't have access to it.
                                                </div>
                                            <?php elseif ($is_edit_mode): ?>
                                                <div class="location-locked-note">
                                                    <i class="bi bi-info-circle"></i>
                                                    Pre-selected from this campaign's own record. Change it only if you intend to move the campaign.
                                                </div>
                                            <?php else: ?>
                                                <div class="form-helper">Location for this Campaign to be Active</div>
                                            <?php endif; ?>
                                            <div class="form-helper" id="PK_LOCATION_error" style="display:none;"></div>
                                        </div>

                                        <!-- Reminder Type -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Reminder Type <span class="required">*</span></label>
                                            <div class="checkbox-group-modern" id="reminderTypeGroup">
                                                <label class="checkbox-item">
                                                    <input type="checkbox" name="REMINDER_TYPE[]" value="email" <?= in_array('email', $REMINDER_TYPE) ? 'checked' : '' ?> <?= $location_access_denied ? 'disabled' : '' ?>>
                                                    Email
                                                </label>
                                                <label class="checkbox-item">
                                                    <input type="checkbox" name="REMINDER_TYPE[]" value="text" <?= in_array('text', $REMINDER_TYPE) ? 'checked' : '' ?> <?= $location_access_denied ? 'disabled' : '' ?>>
                                                    Text Message
                                                </label>
                                            </div>
                                            <div class="form-helper" id="reminderTypeHelper">Select one or both reminder types</div>
                                        </div>

                                        <!-- Subject -->
                                        <div class="form-group-modern">
                                            <label class="form-label">
                                                Subject
                                                <span class="subject-optional" id="subjectRequiredLabel">(Required for Email)</span>
                                            </label>
                                            <input type="text" class="form-control-modern" id="SUBJECT" name="SUBJECT" placeholder="Enter subject" value="<?php echo htmlspecialchars($SUBJECT) ?>" <?= $location_access_denied ? 'disabled' : '' ?>>
                                            <div class="form-helper" id="subjectHelper">The subject line that will appear in the <?= in_array('email', $REMINDER_TYPE) ? 'email' : 'text message' ?></div>
                                        </div>

                                        <!-- Target Audience -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Target Audience <span class="required">*</span></label>
                                            <div class="audience-checkbox-container" id="audienceContainer">
                                                <div class="checkbox-item">
                                                    <input type="checkbox" class="form-check-input audience-checkbox" name="OPERATION[]" value="inactive_customers" id="audience_inactive" <?= in_array('inactive_customers', $OPERATION) ? 'checked' : '' ?> <?= $location_access_denied ? 'disabled' : '' ?>>
                                                    <label for="audience_inactive">All Inactive Customers</label>
                                                </div>
                                                <div class="checkbox-item">
                                                    <input type="checkbox" class="form-check-input audience-checkbox" name="OPERATION[]" value="active_customers" id="audience_active" <?= in_array('active_customers', $OPERATION) ? 'checked' : '' ?> <?= $location_access_denied ? 'disabled' : '' ?>>
                                                    <label for="audience_active">All Active Customers</label>
                                                </div>
                                                <div class="checkbox-item">
                                                    <input type="checkbox" class="form-check-input audience-checkbox" name="OPERATION[]" value="tags" id="audience_tags" <?= in_array('tags', $OPERATION) ? 'checked' : '' ?> <?= $location_access_denied ? 'disabled' : '' ?>>
                                                    <label for="audience_tags">By Tags</label>
                                                </div>
                                                <div class="checkbox-item">
                                                    <input type="checkbox" class="form-check-input audience-checkbox" name="OPERATION[]" value="leads" id="audience_leads" <?= in_array('leads', $OPERATION) ? 'checked' : '' ?> <?= $location_access_denied ? 'disabled' : '' ?>>
                                                    <label for="audience_leads">Leads</label>
                                                </div>
                                            </div>
                                            <div class="form-helper" id="audienceHelper">Select one or more target audience options. Additional configuration will appear based on your selection.</div>
                                        </div>

                                        <!-- Tags -->
                                        <div class="form-group-modern conditional-field <?= in_array('tags', $OPERATION) ? 'visible' : '' ?>" id="tags_field">
                                            <label class="form-label">Select Tags <span class="required">*</span></label>
                                            <div class="checkbox-container" id="tagsCheckboxContainer">
                                                <div class="checkbox-item select-all-item">
                                                    <input type="checkbox" id="selectAllTags" class="form-check-input" <?= $location_access_denied ? 'disabled' : '' ?>>
                                                    <label for="selectAllTags" class="fw-semibold">Select All Tags</label>
                                                </div>
                                                <?php foreach ($all_tags as $tag): ?>
                                                    <div class="checkbox-item">
                                                        <input type="checkbox" class="form-check-input tag-checkbox" name="PK_USER_TAG[]" value="<?= $tag['id'] ?>" id="tag_<?= $tag['id'] ?>" <?= in_array($tag['id'], $selected_tag) ? 'checked' : '' ?> <?= $location_access_denied ? 'disabled' : '' ?>>
                                                        <label for="tag_<?= $tag['id'] ?>"><?= htmlspecialchars($tag['name']) ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="selected-count" id="tagsSelectedCount">Selected: <span id="tagsCount"><?= count($selected_tag) ?></span></div>
                                            <div class="form-helper" id="tagsHelper">Check the boxes to select multiple tags</div>
                                        </div>

                                        <!-- Lead Status -->
                                        <div class="form-group-modern conditional-field <?= in_array('leads', $OPERATION) ? 'visible' : '' ?>" id="lead_status_field">
                                            <label class="form-label">Select Lead Statuses <span class="required">*</span></label>
                                            <div class="checkbox-container" id="leadStatusCheckboxContainer">
                                                <div class="checkbox-item select-all-item">
                                                    <input type="checkbox" id="selectAllLeadStatuses" class="form-check-input" <?= $location_access_denied ? 'disabled' : '' ?>>
                                                    <label for="selectAllLeadStatuses" class="fw-semibold">Select All Lead Statuses</label>
                                                </div>
                                                <?php foreach ($all_lead_statuses as $status): ?>
                                                    <div class="checkbox-item">
                                                        <input type="checkbox" class="form-check-input lead-status-checkbox" name="PK_LEAD_STATUS[]" value="<?= $status['id'] ?>" id="lead_status_<?= $status['id'] ?>" <?= in_array($status['id'], $selected_lead_statuses) ? 'checked' : '' ?> <?= $location_access_denied ? 'disabled' : '' ?>>
                                                        <label for="lead_status_<?= $status['id'] ?>"><?= htmlspecialchars($status['name']) ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="selected-count" id="leadStatusSelectedCount">Selected: <span id="leadStatusCount"><?= count($selected_lead_statuses) ?></span></div>
                                            <div class="form-helper" id="leadStatusHelper">Check the boxes to select multiple lead statuses</div>
                                        </div>

                                        <!-- Content -->
                                        <div class="form-group-modern full-width">
                                            <label class="form-label" id="contentLabel">Content <span class="required">*</span></label>
                                            <div class="content-editable" contenteditable="<?= $location_access_denied ? 'false' : 'true' ?>" id="contentEditable" <?= $location_access_denied ? 'style="background:var(--gray-100);cursor:not-allowed;"' : '' ?>><?= htmlspecialchars_decode($CONTENT) ?></div>
                                            <input type="hidden" name="CONTENT" id="CONTENT" value="<?= htmlspecialchars($CONTENT) ?>">
                                            <div class="form-helper" id="contentHelper">Click variable buttons below to insert dynamic fields into your content.</div>

                                            <div class="variables-section">
                                                <span class="text-muted extra-small d-block mb-1">Insert Variables</span>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Student Name" <?= $location_access_denied ? 'disabled' : '' ?>>Student Name</button>
                                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Location" <?= $location_access_denied ? 'disabled' : '' ?>>Location</button>
                                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Service Provider Name" <?= $location_access_denied ? 'disabled' : '' ?>>Service Provider Name</button>
                                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Corporation Name" <?= $location_access_denied ? 'disabled' : '' ?>>Corporation Name</button>
                                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Student ID" <?= $location_access_denied ? 'disabled' : '' ?>>Student ID</button>
                                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Course Name" <?= $location_access_denied ? 'disabled' : '' ?>>Course Name</button>
                                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Date" <?= $location_access_denied ? 'disabled' : '' ?>>Date</button>
                                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Time" <?= $location_access_denied ? 'disabled' : '' ?>>Time</button>
                                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Instructor Name" <?= $location_access_denied ? 'disabled' : '' ?>>Instructor Name</button>
                                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Class Name" <?= $location_access_denied ? 'disabled' : '' ?>>Class Name</button>
                                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Campaign Name" <?= $location_access_denied ? 'disabled' : '' ?>>Campaign Name</button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Schedule -->
                                        <div class="form-group-modern full-width">
                                            <label class="form-label">Schedule Campaign</label>
                                            <div class="schedule-toggle">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="scheduleCheckbox" <?= (!empty($SCHEDULE_DATETIME) && $SCHEDULE_DATETIME != '0000-00-00 00:00:00') ? 'checked' : '' ?> <?= $location_access_denied ? 'disabled' : '' ?>>
                                                    <label class="form-check-label" for="scheduleCheckbox">
                                                        Schedule for later
                                                    </label>
                                                </div>
                                                <?php if (!empty($SCHEDULE_DATETIME) && $SCHEDULE_DATETIME != '0000-00-00 00:00:00'): ?>
                                                    <div class="schedule-info">
                                                        <i class="bi bi-clock-history"></i>
                                                        <span>Scheduled for <strong><?= date('M j, Y g:i A', strtotime($SCHEDULE_DATETIME)) ?></strong></span>
                                                        <?php if (!empty($TIMEZONE)): ?>
                                                            <span class="timezone-badge"><i class="bi bi-globe2"></i> <?= htmlspecialchars($TIMEZONE) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="schedule-fields <?= (!empty($SCHEDULE_DATETIME) && $SCHEDULE_DATETIME != '0000-00-00 00:00:00') ? 'visible' : '' ?>" id="scheduleFields">
                                                <div class="form-group-modern">
                                                    <label class="form-label">Date <span class="required" id="scheduleDateRequired" style="display:none;">*</span></label>
                                                    <input type="date" class="form-control-modern" id="SCHEDULE_DATE" name="SCHEDULE_DATE" value="<?= htmlspecialchars($SCHEDULE_DATE) ?>" min="<?= date('Y-m-d') ?>" <?= $location_access_denied ? 'disabled' : '' ?>>
                                                    <div class="datetime-helper">Select the date when campaign should be sent</div>
                                                </div>
                                                <div class="form-group-modern">
                                                    <label class="form-label">Time <span class="required" id="scheduleTimeRequired" style="display:none;">*</span></label>
                                                    <input type="time" class="form-control-modern" id="SCHEDULE_TIME" name="SCHEDULE_TIME" value="<?= htmlspecialchars($SCHEDULE_TIME) ?>" step="60" <?= $location_access_denied ? 'disabled' : '' ?>>
                                                    <div class="datetime-helper">Select the time when campaign should be sent</div>
                                                </div>
                                            </div>
                                            <div class="form-helper" id="scheduleHelper">
                                                <?php if (!empty($TIMEZONE)): ?>
                                                    Timezone: <strong><?= htmlspecialchars($TIMEZONE) ?></strong> (based on location)
                                                <?php else: ?>
                                                    The timezone will be automatically set based on the selected location
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Active Status -->

                                        <div class="form-group-modern">
                                            <label class="form-label">Status</label>
                                            <div class="radio-group-modern">
                                                <label class="radio-item">
                                                    <input type="radio" id="ACTIVE1" name="ACTIVE" value="1" <?php echo $ACTIVE == '1' ? 'checked' : '' ?> <?= $location_access_denied ? 'disabled' : '' ?>>
                                                    Active
                                                </label>
                                                <label class="radio-item">
                                                    <input type="radio" id="ACTIVE2" name="ACTIVE" value="0" <?php echo $ACTIVE == '0' ? 'checked' : '' ?> <?= $location_access_denied ? 'disabled' : '' ?>>
                                                    Inactive
                                                </label>
                                            </div>
                                        </div>


                                        <?php if (!empty($_GET['id'])): ?>
                                            <input type="hidden" name="PK_CAMPAIGN_ID" value="<?php echo intval($_GET['id']) ?>">
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-actions">
                                        <?php if ($location_access_denied): ?>
                                            <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_marketings.php'">
                                                <i class="fas fa-arrow-left"></i> Back to Campaigns
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn-modern btn-modern-primary" id="submitBtn">
                                                <i class="fas fa-save"></i>
                                                <?php if (empty($_GET['id'])): ?>
                                                    Create Campaign
                                                <?php else: ?>
                                                    Update Campaign
                                                <?php endif; ?>
                                            </button>
                                            <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_marketings.php'">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        <?php endif; ?>
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

    <script>
        // ---------------------------------------------------------------
        // Field-level error helper
        // ---------------------------------------------------------------
        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.classList.add('is-invalid');
            }
            const helper = document.getElementById(fieldId + '_error');
            if (helper) {
                helper.textContent = message;
                helper.classList.add('error');
                helper.style.display = 'block';
            }
        }

        function clearFieldError(fieldId) {
            const field = document.getElementById(fieldId);
            if (field) field.classList.remove('is-invalid');
            const helper = document.getElementById(fieldId + '_error');
            if (helper) {
                helper.classList.remove('error');
                helper.style.display = 'none';
            }
        }

        function clearAllErrors() {
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('.form-helper.error').forEach(el => {
                el.classList.remove('error');
                el.style.display = 'none';
            });
        }

        // --- INSERT VARIABLE INTO CONTENTEDITABLE DIV ---
        function insertVariable(varName) {
            const editable = document.getElementById('contentEditable');
            if (!editable || editable.getAttribute('contenteditable') === 'false') return;

            editable.focus();
            const selection = window.getSelection();
            let range;

            if (selection.rangeCount > 0) {
                range = selection.getRangeAt(0);
            } else {
                range = document.createRange();
                range.setStart(editable, editable.childNodes.length);
                range.collapse(true);
                selection.addRange(range);
            }

            const variableSpan = document.createElement('span');
            variableSpan.className = 'variable-badge';
            variableSpan.setAttribute('contenteditable', 'false');
            variableSpan.textContent = varName;

            range.deleteContents();
            range.insertNode(variableSpan);

            const spaceNode = document.createTextNode('\u00A0');
            range.setStartAfter(variableSpan);
            range.insertNode(spaceNode);

            range.setStartAfter(spaceNode);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);

            updateContentInput();
        }

        function updateContentInput() {
            const editable = document.getElementById('contentEditable');
            const hiddenInput = document.getElementById('CONTENT');
            if (editable && hiddenInput) {
                hiddenInput.value = editable.innerHTML;
            }
        }

        function updateContentLabels() {
            const emailEl = document.querySelector('input[name="REMINDER_TYPE[]"][value="email"]');
            const textEl = document.querySelector('input[name="REMINDER_TYPE[]"][value="text"]');
            if (!emailEl || !textEl) return;

            const emailChecked = emailEl.checked;
            const textChecked = textEl.checked;
            const contentLabel = document.getElementById('contentLabel');
            const contentHelper = document.getElementById('contentHelper');
            const subjectHelper = document.getElementById('subjectHelper');
            const subjectRequiredLabel = document.getElementById('subjectRequiredLabel');
            const subjectInput = document.getElementById('SUBJECT');

            let labelText = 'Content';
            if (emailChecked && textChecked) {
                labelText = 'Content (Email & Text Message)';
                contentHelper.textContent = 'Enter your content for both email and text message. Click variable buttons below to insert dynamic fields.';
                subjectHelper.textContent = 'Subject is required for email (optional for text message)';
                subjectRequiredLabel.textContent = '(Required for Email, Optional for Text)';
            } else if (emailChecked) {
                labelText = 'Email Content';
                contentHelper.textContent = 'Enter your email content. Click variable buttons below to insert dynamic fields.';
                subjectHelper.textContent = 'The subject line that will appear in the email';
                subjectRequiredLabel.textContent = '(Required for Email)';
            } else if (textChecked) {
                labelText = 'Text Message Content';
                contentHelper.textContent = 'Enter your text message content. Click variable buttons below to insert dynamic fields.';
                subjectHelper.textContent = 'Subject is optional for text message';
                subjectRequiredLabel.textContent = '(Optional for Text Message)';
            }

            contentLabel.innerHTML = labelText + ' <span class="required">*</span>';

            if (emailChecked) {
                subjectInput.setAttribute('required', 'required');
                subjectInput.classList.remove('optional');
            } else {
                subjectInput.removeAttribute('required');
                subjectInput.classList.add('optional');
            }
        }

        function toggleConditionalFields() {
            const tagsChecked = document.getElementById('audience_tags').checked;
            const leadsChecked = document.getElementById('audience_leads').checked;
            const tagsField = document.getElementById('tags_field');
            const leadStatusField = document.getElementById('lead_status_field');

            tagsField.classList.remove('visible');
            leadStatusField.classList.remove('visible');

            if (tagsChecked) {
                tagsField.classList.add('visible');
                updateTagsCount();
            }
            if (leadsChecked) {
                leadStatusField.classList.add('visible');
                updateLeadStatusCount();
            }

            const audienceHelper = document.getElementById('audienceHelper');
            const selected = document.querySelectorAll('.audience-checkbox:checked');
            if (selected.length === 0) {
                audienceHelper.style.color = 'var(--danger-color)';
                audienceHelper.textContent = 'Please select at least one target audience option';
            } else {
                audienceHelper.style.color = 'var(--gray-400)';
                audienceHelper.textContent = 'Select one or more target audience options. Additional configuration will appear based on your selection.';
            }
        }

        function updateTagsCount() {
            const checked = document.querySelectorAll('.tag-checkbox:checked').length;
            document.getElementById('tagsCount').textContent = checked;
        }

        function updateLeadStatusCount() {
            const checked = document.querySelectorAll('.lead-status-checkbox:checked').length;
            document.getElementById('leadStatusCount').textContent = checked;
        }

        // ---------------------------------------------------------------
        // Event bindings — only attach if the elements exist and aren't disabled
        // ---------------------------------------------------------------
        const isReadOnly = <?= $location_access_denied ? 'true' : 'false' ?>;

        document.addEventListener('DOMContentLoaded', function() {

            // Initial state
            updateContentLabels();
            toggleConditionalFields();
            updateTagsCount();
            updateLeadStatusCount();

            const scheduleCheckbox = document.getElementById('scheduleCheckbox');
            if (scheduleCheckbox && scheduleCheckbox.checked) {
                scheduleCheckbox.dispatchEvent(new Event('change'));
            }

            const editable = document.getElementById('contentEditable');
            const hiddenInput = document.getElementById('CONTENT');
            if (editable && hiddenInput && !editable.innerHTML.trim()) {
                const content = hiddenInput.value;
                if (content) {
                    editable.innerHTML = content;
                }
            }

            // --- SELECT ALL TAGS ---
            const selectAllTags = document.getElementById('selectAllTags');
            if (selectAllTags) {
                selectAllTags.addEventListener('change', function() {
                    document.querySelectorAll('.tag-checkbox').forEach(cb => cb.checked = this.checked);
                    updateTagsCount();
                });
            }

            // --- SELECT ALL LEAD STATUSES ---
            const selectAllLeadStatuses = document.getElementById('selectAllLeadStatuses');
            if (selectAllLeadStatuses) {
                selectAllLeadStatuses.addEventListener('change', function() {
                    document.querySelectorAll('.lead-status-checkbox').forEach(cb => cb.checked = this.checked);
                    updateLeadStatusCount();
                });
            }

            // --- SCHEDULE TOGGLE ---
            if (scheduleCheckbox) {
                scheduleCheckbox.addEventListener('change', function() {
                    const scheduleFields = document.getElementById('scheduleFields');
                    const dateInput = document.getElementById('SCHEDULE_DATE');
                    const timeInput = document.getElementById('SCHEDULE_TIME');
                    const dateRequired = document.getElementById('scheduleDateRequired');
                    const timeRequired = document.getElementById('scheduleTimeRequired');

                    if (this.checked) {
                        scheduleFields.classList.add('visible');
                        dateInput.setAttribute('required', 'required');
                        timeInput.setAttribute('required', 'required');
                        dateRequired.style.display = 'inline';
                        timeRequired.style.display = 'inline';

                        if (!dateInput.value) {
                            const tomorrow = new Date();
                            tomorrow.setDate(tomorrow.getDate() + 1);
                            dateInput.value = tomorrow.toISOString().split('T')[0];
                        }
                        if (!timeInput.value) {
                            timeInput.value = '09:00';
                        }
                    } else {
                        scheduleFields.classList.remove('visible');
                        dateInput.removeAttribute('required');
                        timeInput.removeAttribute('required');
                        dateRequired.style.display = 'none';
                        timeRequired.style.display = 'none';
                        dateInput.value = '';
                        timeInput.value = '';
                    }
                });
            }

            // --- AUDIENCE CHECKBOXES ---
            document.querySelectorAll('.audience-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    toggleConditionalFields();
                    const checked = document.querySelectorAll('.audience-checkbox:checked');
                    if (checked.length === 0) {
                        this.checked = true;
                    }
                    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                });
            });

            // --- REMINDER TYPE CHECKBOXES ---
            document.querySelectorAll('input[name="REMINDER_TYPE[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateContentLabels();
                    const checked = document.querySelectorAll('input[name="REMINDER_TYPE[]"]:checked');
                    if (checked.length === 0) {
                        this.checked = true;
                        alert('Please select at least one reminder type');
                    }
                    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                });
            });

            // --- VARIABLE BUTTONS ---
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.var-btn');
                if (btn && !btn.disabled) {
                    e.preventDefault();
                    const varName = btn.getAttribute('data-var');
                    insertVariable(varName);
                }
            });

            // --- TAGS / LEAD STATUS CHANGES ---
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('tag-checkbox')) {
                    updateTagsCount();
                    const allTags = document.querySelectorAll('.tag-checkbox');
                    const checkedTags = document.querySelectorAll('.tag-checkbox:checked');
                    if (selectAllTags) selectAllTags.checked = allTags.length === checkedTags.length;
                }
                if (e.target.classList.contains('lead-status-checkbox')) {
                    updateLeadStatusCount();
                    const allStatuses = document.querySelectorAll('.lead-status-checkbox');
                    const checkedStatuses = document.querySelectorAll('.lead-status-checkbox:checked');
                    if (selectAllLeadStatuses) selectAllLeadStatuses.checked = allStatuses.length === checkedStatuses.length;
                }
            });

            if (editable) {
                editable.addEventListener('input', updateContentInput);
            }

            // --- CLEAR INVALID ON INPUT ---
            document.querySelectorAll('.form-control-modern').forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.classList.remove('is-invalid');
                        this.style.borderColor = 'var(--gray-200)';
                        const helper = document.getElementById(this.id + '_error');
                        if (helper) helper.style.display = 'none';
                    }
                });
                input.addEventListener('change', function() {
                    if (this.value) {
                        this.classList.remove('is-invalid');
                        this.style.borderColor = 'var(--gray-200)';
                        const helper = document.getElementById(this.id + '_error');
                        if (helper) helper.style.display = 'none';
                    }
                });
            });
        });

        // ---------------------------------------------------------------
        // FORM VALIDATION — show inline messages next to each field
        // ---------------------------------------------------------------
        const campaignForm = document.getElementById('campaignForm');
        if (campaignForm) {
            campaignForm.addEventListener('submit', function(e) {
                clearAllErrors();

                const templateName = document.getElementById('TEMPLATE_NAME');
                const subject = document.getElementById('SUBJECT');
                const location = document.getElementById('PK_LOCATION');
                const audienceChecked = document.querySelectorAll('.audience-checkbox:checked');
                const tagsChecked = document.querySelectorAll('.tag-checkbox:checked');
                const leadStatusesChecked = document.querySelectorAll('.lead-status-checkbox:checked');
                const editable = document.getElementById('contentEditable');
                const content = editable ? editable.innerHTML.trim() : '';
                const reminderTypes = document.querySelectorAll('input[name="REMINDER_TYPE[]"]:checked');
                const emailEl = document.querySelector('input[name="REMINDER_TYPE[]"][value="email"]');
                const emailChecked = emailEl ? emailEl.checked : false;
                const scheduleChecked = document.getElementById('scheduleCheckbox') ? document.getElementById('scheduleCheckbox').checked : false;
                const scheduleDate = document.getElementById('SCHEDULE_DATE') ? document.getElementById('SCHEDULE_DATE').value : '';
                const scheduleTime = document.getElementById('SCHEDULE_TIME') ? document.getElementById('SCHEDULE_TIME').value : '';

                let isValid = true;
                let errors = [];

                updateContentInput();

                // Campaign name
                if (!templateName.value.trim()) {
                    showFieldError('TEMPLATE_NAME', 'Campaign name is required.');
                    errors.push('Campaign name is required.');
                    isValid = false;
                }

                // Location
                if (!location.value) {
                    showFieldError('PK_LOCATION', 'Please select a location.');
                    errors.push('Please select a location.');
                    isValid = false;
                }

                // Reminder type
                if (reminderTypes.length === 0) {
                    const helper = document.getElementById('reminderTypeHelper');
                    helper.textContent = 'Please select at least one reminder type.';
                    helper.classList.add('error');
                    errors.push('Please select at least one reminder type.');
                    isValid = false;
                }

                // Subject (only required for email)
                if (emailChecked && !subject.value.trim()) {
                    subject.classList.add('is-invalid');
                    const helper = document.getElementById('subjectHelper');
                    helper.classList.add('error');
                    helper.textContent = 'Subject is required when Email is selected.';
                    errors.push('Subject is required when Email is selected.');
                    isValid = false;
                }

                // Target audience
                if (audienceChecked.length === 0) {
                    document.getElementById('audienceContainer').style.borderColor = 'var(--danger-color)';
                    const helper = document.getElementById('audienceHelper');
                    helper.classList.add('error');
                    helper.textContent = 'Please select at least one target audience.';
                    errors.push('Please select at least one target audience.');
                    isValid = false;
                }

                // Tags
                const tagsAudienceChecked = document.getElementById('audience_tags').checked;
                if (tagsAudienceChecked && tagsChecked.length === 0) {
                    document.getElementById('tagsCheckboxContainer').style.borderColor = 'var(--danger-color)';
                    const helper = document.getElementById('tagsHelper');
                    helper.classList.add('error');
                    helper.textContent = 'Please select at least one tag.';
                    errors.push('Please select at least one tag.');
                    isValid = false;
                }

                // Lead statuses
                const leadsAudienceChecked = document.getElementById('audience_leads').checked;
                if (leadsAudienceChecked && leadStatusesChecked.length === 0) {
                    document.getElementById('leadStatusCheckboxContainer').style.borderColor = 'var(--danger-color)';
                    const helper = document.getElementById('leadStatusHelper');
                    helper.classList.add('error');
                    helper.textContent = 'Please select at least one lead status.';
                    errors.push('Please select at least one lead status.');
                    isValid = false;
                }

                // Content
                const isEmpty = !content || content === '<p><br></p>' || content === '<br>' || content === '<div><br></div>';
                if (isEmpty) {
                    editable.classList.add('is-invalid');
                    const helper = document.getElementById('contentHelper');
                    helper.classList.add('error');
                    helper.textContent = 'Please enter content.';
                    errors.push('Please enter content.');
                    isValid = false;
                }

                // Schedule
                if (scheduleChecked) {
                    if (!scheduleDate) {
                        showFieldError('SCHEDULE_DATE', 'Please pick a date.');
                        errors.push('Please pick a scheduled date.');
                        isValid = false;
                    }
                    if (!scheduleTime) {
                        showFieldError('SCHEDULE_TIME', 'Please pick a time.');
                        errors.push('Please pick a scheduled time.');
                        isValid = false;
                    }
                    if (scheduleDate && scheduleTime) {
                        const selectedDate = new Date(scheduleDate + ' ' + scheduleTime);
                        if (selectedDate < new Date()) {
                            showFieldError('SCHEDULE_DATE', 'Date/time cannot be in the past.');
                            showFieldError('SCHEDULE_TIME', 'Date/time cannot be in the past.');
                            errors.push('Scheduled date/time cannot be in the past.');
                            isValid = false;
                        }
                    }
                }

                // If anything failed, stop submit + summarise
                if (!isValid) {
                    e.preventDefault();

                    // Remove any previous summary
                    const existing = document.getElementById('saveErrorsBlock');
                    if (existing) existing.remove();

                    const summary = document.createElement('div');
                    summary.id = 'saveErrorsBlock';
                    summary.className = 'save-errors';
                    summary.innerHTML = '<strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Cannot save yet — please fix the following:</strong><ul>' +
                        errors.map(err => '<li>' + err + '</li>').join('') +
                        '</ul>';
                    campaignForm.parentNode.insertBefore(summary, campaignForm);

                    // Scroll to first error
                    const firstError = campaignForm.querySelector('.is-invalid');
                    if (firstError) {
                        firstError.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        try {
                            firstError.focus({
                                preventScroll: true
                            });
                        } catch (e) {}
                    } else {
                        summary.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }
            });
        }
    </script>

</body>

</html>