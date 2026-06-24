<?php

use FontLib\Table\Type\glyf;

require_once('../global/config.php');
global $db;
global $AMI_ENABLE;

$userType = "Users";
$order = $db->Execute("SELECT SORT_ORDER FROM DOA_ROLES WHERE ACTIVE='1' AND PK_ROLES = " . $_SESSION['PK_ROLES']);
$sort_order = $order->fields['SORT_ORDER'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$CREATE_LOGIN = 0;

if (empty($_GET['id']))
    $title = "Add " . $userType;
else
    $title = "Edit " . $userType;

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

$PK_USER = 0;
$PK_CUSTOMER_DETAILS = '';
$USER_NAME = '';
$FIRST_NAME = '';
$LAST_NAME = '';
//$TYPE = '';
$EMAIL_ID = '';
$DISPLAY_ORDER = '';
$ARTHUR_MURRAY_ID = '';
$USER_IMAGE = '';
$GENDER = '';
$DOB = '';
$ADDRESS = '';
$ADDRESS_1 = '';
$PK_COUNTRY = '';
$PK_STATES = '';
$CITY = '';
$ZIP = '';
$PHONE = '';
$PK_LOCATION = '';
$USER_TITLE = '';
$NOTES = '';
$PASSWORD = '';
$ACTIVE = '';
$INACTIVE_BY_ADMIN = '';
$CAN_EDIT_ENROLLMENT = '';
$TICKET_SYSTEM_ACCESS = '';
$APPEAR_IN_CALENDAR = 0;

$selected_roles = array(0);
//end
if (!empty($_GET['id'])) {
    $res = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_users.php");
        exit;
    }
    $PK_USER = $res->fields['PK_USER'];
    $USER_NAME = $res->fields['USER_NAME'];
    $FIRST_NAME = $res->fields['FIRST_NAME'];
    $LAST_NAME = $res->fields['LAST_NAME'];
    //$TYPE = $res->fields['TYPE'];
    $EMAIL_ID = $res->fields['EMAIL_ID'];
    $DISPLAY_ORDER = $res->fields['DISPLAY_ORDER'];
    $ARTHUR_MURRAY_ID = $res->fields['ARTHUR_MURRAY_ID'];
    $USER_IMAGE = $res->fields['USER_IMAGE'];
    $GENDER = $res->fields['GENDER'];
    $DOB = $res->fields['DOB'];
    $ADDRESS = $res->fields['ADDRESS'];
    $ADDRESS_1 = $res->fields['ADDRESS_1'];
    $PK_COUNTRY = $res->fields['PK_COUNTRY'];
    $PK_STATES = $res->fields['PK_STATES'];
    $CITY = $res->fields['CITY'];
    $ZIP = $res->fields['ZIP'];
    $PHONE = $res->fields['PHONE'];
    $NOTES = $res->fields['NOTES'];
    $ACTIVE = $res->fields['ACTIVE'];
    $PASSWORD = $res->fields['PASSWORD'];
    $INACTIVE_BY_ADMIN = $res->fields['INACTIVE_BY_ADMIN'];
    $CAN_EDIT_ENROLLMENT = $res->fields['CAN_EDIT_ENROLLMENT'];
    $CREATE_LOGIN = $res->fields['CREATE_LOGIN'];
    $APPEAR_IN_CALENDAR = $res->fields['APPEAR_IN_CALENDAR'];
    $TICKET_SYSTEM_ACCESS = $res->fields['TICKET_SYSTEM_ACCESS'];

    if (!empty($_GET['id'])) {
        $PK_USER = $_GET['id'];
        $selected_roles_row = $db->Execute("SELECT PK_ROLES FROM `DOA_USER_ROLES` WHERE `PK_USER` = '$PK_USER'");
        while (!$selected_roles_row->EOF) {
            $selected_roles[] = $selected_roles_row->fields['PK_ROLES'];
            $selected_roles_row->MoveNext();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
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

    /* Breadcrumb */
    .breadcrumb-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .breadcrumb-wrapper h4 {
        font-size: 24px;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0;
        letter-spacing: -0.025em;
    }

    .breadcrumb-wrapper h4 i {
        color: var(--primary-color);
        margin-right: 10px;
    }

    .breadcrumb-nav {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-500);
    }

    .breadcrumb-nav a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .breadcrumb-nav a:hover {
        color: var(--primary-dark);
    }

    .breadcrumb-nav .separator {
        color: var(--gray-300);
    }

    .breadcrumb-nav .current {
        color: var(--gray-700);
        font-weight: 500;
    }

    /* Card */
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
        flex-wrap: wrap;
        gap: 12px;
    }

    .card-modern .card-header h5 {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .card-modern .card-header h5 i {
        color: var(--primary-color);
    }

    .card-modern .card-header .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 500;
    }

    .card-modern .card-header .status-indicator.active {
        background: #D1FAE5;
        color: #065F46;
    }

    .card-modern .card-header .status-indicator.inactive {
        background: #FEE2E2;
        color: #991B1B;
    }

    .card-modern .card-header .status-indicator i {
        font-size: 8px;
        margin-right: 0;
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

    /* Tabs */
    .tabs-modern {
        display: flex;
        gap: 4px;
        border-bottom: 2px solid var(--gray-200);
        padding-bottom: 0;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .tabs-modern .tab-item {
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 500;
        color: var(--gray-500);
        cursor: pointer;
        border: none;
        background: transparent;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s ease;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 8px;
        border-radius: 0;
    }

    .tabs-modern .tab-item:hover {
        color: var(--gray-700);
        background: var(--gray-50);
    }

    .tabs-modern .tab-item.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
        font-weight: 600;
    }

    .tabs-modern .tab-item i {
        font-size: 16px;
    }

    .tab-content-modern {
        padding: 4px 0;
    }

    .tab-pane-modern {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-pane-modern.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Form */
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

    /* Tooltip wrapper - ensure it's positioned correctly */
    .tooltip-bubble {
        position: relative;
        display: inline-block;
        cursor: help;
        color: var(--primary-color, #39B54A);
        vertical-align: middle;
        margin-left: 2px;
    }

    /* The tooltip text container */
    .tooltip-bubble .tooltip-text {
        position: absolute;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%) translateY(0);
        min-width: 160px;
        max-width: 260px;
        background: var(--primary-color, #39B54A);
        color: #fff;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        line-height: 1.4;
        text-align: center;
        white-space: normal;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        pointer-events: none;
        font-weight: normal;
        text-transform: none;
        letter-spacing: normal;
    }

    /* Tooltip arrow */
    .tooltip-bubble .tooltip-text::after {
        content: "";
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border-width: 6px;
        border-style: solid;
        border-color: var(--primary-color, #39B54A) transparent transparent transparent;
    }

    /* Show tooltip on hover */
    .tooltip-bubble:hover .tooltip-text,
    .tooltip-bubble:focus-within .tooltip-text {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(-4px);
        pointer-events: auto;
    }

    /* Icon styling inside tooltip */
    .tooltip-bubble i,
    .tooltip-bubble .ti-help-alt {
        font-size: 14px;
        color: var(--primary-color, #39B54A);
        cursor: help;
        outline: none;
        display: inline-block;
        line-height: 1;
    }

    /* Make sure the tooltip doesn't clip in smaller containers */
    .tooltip-bubble {
        overflow: visible !important;
    }

    /* Ensure labels with tooltips don't clip */
    .form-label,
    label {
        overflow: visible !important;
        position: relative;
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

    .form-control-modern:read-only {
        background: var(--gray-50);
        cursor: default;
    }

    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    textarea.form-control-modern {
        min-height: 80px;
        resize: vertical;
    }

    /* Radio & Checkbox */
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
        align-items: center;
        gap: 5px;
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
        padding: 23px 0;
    }

    .checkbox-group-modern input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-color);
        cursor: pointer;
        flex-shrink: 0;
    }

    /* Buttons */
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

    .btn-modern-danger:hover {

        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220, 38, 38, 0.25);
    }

    .btn-modern-sm {
        padding: 6px 18px;
        font-size: 13px;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 28px;
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

        .breadcrumb-wrapper {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    /* Input group */
    .input-group-modern {
        display: flex;
        align-items: center;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .input-group-modern:focus-within {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .input-group-modern .form-control-modern {
        border: none;
        border-radius: 0;
        flex: 1;
    }

    .input-group-modern .form-control-modern:focus {
        box-shadow: none;
    }

    .input-group-modern .input-group-text {
        padding: 10px 14px;
        background: var(--gray-50);
        color: var(--gray-500);
        font-size: 14px;
        font-weight: 500;
        border-left: 1px solid var(--gray-200);
    }

    /* SumoSelect override */
    .SumoSelect {
        width: 100% !important;
    }

    .SumoSelect>.CaptionCont {
        border: 1.5px solid var(--gray-200) !important;
        border-radius: var(--radius-sm) !important;
        padding: 8px 12px !important;
        min-height: 44px;
        transition: all 0.2s ease;
    }

    .SumoSelect>.CaptionCont:hover {
        border-color: var(--gray-300) !important;
    }

    .SumoSelect.open>.CaptionCont {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1) !important;
    }

    .SumoSelect>.CaptionCont>span {
        color: var(--gray-700) !important;
        font-size: 14px !important;
    }

    .SumoSelect>.CaptionCont>label {
        margin: 0 !important;
        padding: 0 !important;
    }

    .SumoSelect>.CaptionCont>label>i {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E") !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        width: 12px !important;
        height: 12px !important;
    }

    .SumoSelect .optWrapper {
        border: 1.5px solid var(--gray-200) !important;
        border-radius: var(--radius-sm) !important;
        box-shadow: var(--shadow-md) !important;
    }

    .SumoSelect .optWrapper .options li.opt {
        padding: 8px 14px !important;
        font-size: 14px !important;
        color: var(--gray-700) !important;
        transition: background 0.2s;
    }

    .SumoSelect .optWrapper .options li.opt:hover {
        background: var(--gray-50) !important;
    }

    .SumoSelect .optWrapper .options li.opt.selected {
        background: #F0FDF4 !important;
        color: var(--primary-color) !important;
    }

    .SumoSelect .optWrapper .options li.opt.selected::before {
        content: "✓ ";
        color: var(--primary-color);
    }

    .SumoSelect .select-all {
        padding: 8px 14px !important;
        font-size: 14px !important;
        color: var(--gray-700) !important;
        border-bottom: 1px solid var(--gray-200) !important;
    }

    .SumoSelect .select-all label {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        cursor: pointer !important;
    }

    .SumoSelect .select-all input[type="checkbox"] {
        accent-color: var(--primary-color) !important;
        width: 16px !important;
        height: 16px !important;
        cursor: pointer !important;
    }

    .SumoSelect .search input {
        border: 1px solid var(--gray-200) !important;
        border-radius: var(--radius-sm) !important;
        padding: 6px 10px !important;
        font-size: 14px !important;
    }

    .SumoSelect .search input:focus {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1) !important;
    }

    /* Password strength */
    .password-strength {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 4px;
    }

    .password-strength .progress-bar {
        height: 6px !important;
        border-radius: 3px;
    }

    /* Image preview */
    .image-preview {
        width: 120px;
        height: 120px;
        border-radius: var(--radius-sm);
        overflow: hidden;
        border: 2px solid var(--gray-200);
        margin-top: 8px;
    }

    .image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .image-preview a {
        display: block;
        width: 100%;
        height: 100%;
    }

    /* Rates section */
    .rate-row {
        display: grid;
        grid-template-columns: 2fr 1fr 4fr;
        gap: 16px;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid var(--gray-100);
    }

    .rate-row:last-child {
        border-bottom: none;
    }

    .rate-row .rate-label {
        font-size: 14px;
        color: var(--gray-700);
    }

    @media (max-width: 768px) {
        .rate-row {
            grid-template-columns: 1fr;
            gap: 8px;
            padding: 12px 0;
        }
    }

    /* Service commission row */
    .commission-row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 16px;
        align-items: center;
        padding: 8px 0;
    }

    @media (max-width: 768px) {
        .commission-row {
            grid-template-columns: 1fr;
            gap: 8px;
        }
    }

    /* Document row */
    .document-row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 16px;
        align-items: end;
        padding: 12px 0;
        border-bottom: 1px solid var(--gray-100);
    }

    .document-row:last-child {
        border-bottom: none;
    }

    @media (max-width: 768px) {
        .document-row {
            grid-template-columns: 1fr;
            gap: 12px;
        }
    }

    /* Comment table */
    .table-modern {
        width: 100% !important;
        border-collapse: collapse;
        font-size: 14px;
        min-width: 600px;
    }

    .table-modern thead th {
        background: var(--gray-50);
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        color: var(--gray-600);
        border-bottom: 2px solid var(--gray-200);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }

    .table-modern tbody td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--gray-100);
        color: var(--gray-700);
        vertical-align: middle;
    }

    .table-modern tbody tr:hover {
        background: var(--gray-50);
    }

    .table-modern .action-icons a {
        color: var(--gray-500);
        transition: color 0.2s;
        margin-right: 8px;
    }

    .table-modern .action-icons a:hover {
        color: var(--primary-color);
    }

    .table-modern .active-box-green {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--success-color);
    }

    .table-modern .active-box-red {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--danger-color);
    }

    /* Modal */
    .modal-modern {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        overflow: auto;
    }

    .modal-modern .modal-content {
        background: #fff;
        margin: 5% auto;
        padding: 0;
        width: 50%;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-modern .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-modern .modal-header h4 {
        font-size: 18px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
    }

    .modal-modern .modal-header .close {
        font-size: 28px;
        font-weight: 400;
        color: var(--gray-400);
        cursor: pointer;
        transition: color 0.2s;
        background: none;
        border: none;
        line-height: 1;
    }

    .modal-modern .modal-header .close:hover {
        color: var(--gray-900);
    }

    .modal-modern .modal-body {
        padding: 24px;
    }

    @media (max-width: 768px) {
        .modal-modern .modal-content {
            width: 95%;
            margin: 10% auto;
        }
    }

    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
    }

    .full-width {
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .full-width {
            grid-column: 1;
        }
    }

    .mt-10 {
        margin-top: 10px;
    }

    .mb-20 {
        margin-bottom: 20px;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    .text-danger {
        color: var(--danger-color);
    }

    .text-primary {
        color: var(--primary-color);
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">

                <!-- Main Grid -->
                <div class="row g-4">
                    <!-- Sidebar -->
                    <div class="col-12 col-md-4 col-xl-2">
                        <?php include 'layout/setup_sidebar.php'; ?>
                    </div>

                    <!-- Main Form -->
                    <div class="col-12 col-md-8 col-xl-10">

                        <!-- User Selector -->
                        <!-- <?php if (!empty($_GET['id'])): ?>
                            <div style="margin-bottom: 16px;">
                                <select class="form-control-modern" style="max-width: 300px;" id="user_selector" onchange="editpage(this);">
                                    <option value="">Select User</option>
                                    <?php
                                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USER_ROLES.PK_ROLES IN(2,3,5,6,7,8) AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_USER']; ?>" <?= ($row->fields['PK_USER'] == $_GET['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['NAME']) ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        <?php endif; ?> -->

                        <!-- Main Content -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card-modern">
                                    <div class="card-header">
                                        <h5>
                                            <i class="bi bi-person"></i>
                                            <?= !empty($_GET['id']) ? 'Edit User' : 'Add User' ?>
                                        </h5>
                                        <?php if (!empty($_GET['id'])): ?>
                                            <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                                <i class="fas fa-circle"></i>
                                                <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <!-- Tabs -->
                                        <div class="tabs-modern" role="tablist">
                                            <button class="tab-item active" data-tab="profile" role="tab">
                                                <i class="fas fa-id-badge"></i> Profile
                                            </button>
                                            <button class="tab-item" id="login_tab_btn" data-tab="login" role="tab" style="display: <?= ($CREATE_LOGIN == 1) ? '' : 'none' ?>">
                                                <i class="fas fa-lock"></i> Login Info
                                            </button>
                                            <button class="tab-item" data-tab="rates" role="tab">
                                                <i class="fas fa-money-bill-wave"></i> Rates
                                            </button>
                                            <button class="tab-item" data-tab="service" role="tab">
                                                <i class="fas fa-server"></i> Service
                                            </button>
                                            <button class="tab-item" data-tab="documents" role="tab">
                                                <i class="fas fa-files"></i> Documents
                                            </button>
                                            <button class="tab-item" data-tab="comments" role="tab">
                                                <i class="fas fa-comment"></i> Comments
                                            </button>
                                        </div>

                                        <!-- Tab Content -->
                                        <div class="tab-content-modern">
                                            <!-- Profile Tab -->
                                            <div class="tab-pane-modern active" id="profile" role="tabpanel">
                                                <form class="form-material form-horizontal" id="profile_form">
                                                    <input type="hidden" name="FUNCTION_NAME" value="saveProfileData">
                                                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                                    <input type="hidden" class="TYPE" name="TYPE" value="1">

                                                    <div class="form-grid">
                                                        <!-- First Name -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">First Name <span class="required">*</span></label>
                                                            <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control-modern" placeholder="Enter First Name" required value="<?= htmlspecialchars($FIRST_NAME) ?>">
                                                        </div>

                                                        <!-- Last Name -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Last Name</label>
                                                            <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control-modern" placeholder="Enter Last Name" value="<?= htmlspecialchars($LAST_NAME) ?>">
                                                        </div>

                                                        <!-- Roles -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Roles <span class="required">*</span>
                                                                <span class="tooltip-bubble" tabindex="0">
                                                                    <i class="ti-help-alt" aria-hidden="true"></i>
                                                                    <span class="tooltip-text">Staff Member position and Access</span>
                                                                </span>
                                                            </label>
                                                            <select class="multi_sumo_select_roles" name="PK_ROLES[]" id="PK_ROLES" onchange="showServiceProviderTabs(this)" required multiple>
                                                                <?php
                                                                $row = $db->Execute("SELECT PK_ROLES, ROLES, SORT_ORDER FROM DOA_ROLES WHERE (PK_ROLES IN (" . implode(',', $selected_roles) . ") OR SORT_ORDER > " . $sort_order . ") AND ACTIVE = '1' ORDER BY SORT_ORDER");
                                                                while (!$row->EOF) { ?>
                                                                    <option value="<?php echo $row->fields['PK_ROLES']; ?>" <?= in_array($row->fields['PK_ROLES'], $selected_roles) ? "selected" : "" ?> <?= ($row->fields['SORT_ORDER'] < $sort_order) ? 'disabled' : '' ?>><?= htmlspecialchars($row->fields['ROLES']) ?></option>
                                                                <?php $row->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>

                                                        <!-- Phone -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Phone <span class="required">*</span></label>
                                                            <input type="text" id="PHONE" name="PHONE" class="form-control-modern" placeholder="Enter Phone Number" value="<?php echo htmlspecialchars($PHONE) ?>" required>
                                                            <div id="phone_result" class="form-helper"></div>
                                                            <span id="lblError" style="color: red"></span>
                                                        </div>

                                                        <!-- Email -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Email <span class="required">*</span></label>
                                                            <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control-modern" placeholder="Enter Email Address" value="<?= htmlspecialchars($EMAIL_ID) ?>" required>
                                                            <div id="email_result" class="form-helper"></div>
                                                            <span id="lblError" style="color: red"></span>
                                                        </div>

                                                        <!-- Create Login -->
                                                        <div class="form-group-modern">
                                                            <label class="checkbox-group-modern">
                                                                <input type="checkbox" id="CREATE_LOGIN" name="CREATE_LOGIN" class="form-check-inline" <?= ($CREATE_LOGIN == 1) ? 'checked' : '' ?> onchange="createLogin(this);">
                                                                Create Login
                                                                <span class="tooltip-bubble" tabindex="0">
                                                                    <i class="ti-help-alt" aria-hidden="true"></i>
                                                                    <span class="tooltip-text">Select to create Login for client</span>
                                                                </span>
                                                            </label>
                                                        </div>

                                                        <!-- Appear In Calendar -->
                                                        <div class="form-group-modern">
                                                            <label class="checkbox-group-modern">
                                                                <input type="checkbox" id="APPEAR_IN_CALENDAR" name="APPEAR_IN_CALENDAR" class="form-check-inline" <?= ($APPEAR_IN_CALENDAR == 1) ? 'checked' : '' ?>>
                                                                Appear In Calendar
                                                                <span class="tooltip-bubble" tabindex="0">
                                                                    <i class="ti-help-alt" aria-hidden="true"></i>
                                                                    <span class="tooltip-text">Select to show on calendar</span>
                                                                </span>
                                                            </label>
                                                        </div>

                                                        <!-- Display Order -->
                                                        <div class="form-group-modern" id="display_order">
                                                            <label class="form-label">Display Order
                                                                <span class="tooltip-bubble" tabindex="0">
                                                                    <i class="ti-help-alt" aria-hidden="true"></i>
                                                                    <span class="tooltip-text">Calendar position from Left to Right</span>
                                                                </span>
                                                            </label>
                                                            <input type="text" id="DISPLAY_ORDER" name="DISPLAY_ORDER" class="form-control-modern" placeholder="Enter Display Order" value="<?= htmlspecialchars($DISPLAY_ORDER) ?>">
                                                        </div>

                                                        <!-- Arthur Murray ID -->
                                                        <?php if ($AMI_ENABLE == 1) { ?>
                                                            <div class="form-group-modern">
                                                                <label class="form-label">Arthur Murray ID</label>
                                                                <input type="text" id="ARTHUR_MURRAY_ID" name="ARTHUR_MURRAY_ID" class="form-control-modern" placeholder="Arthur Murray ID" value="<?= htmlspecialchars($ARTHUR_MURRAY_ID) ?>">
                                                            </div>
                                                        <?php } ?>

                                                        <!-- Gender -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Gender</label>
                                                            <select class="form-control-modern" id="GENDER" name="GENDER">
                                                                <option value="">Select Gender</option>
                                                                <option value="Male" <?php if ($GENDER == "Male") echo 'selected = "selected"'; ?>>Male</option>
                                                                <option value="Female" <?php if ($GENDER == "Female") echo 'selected = "selected"'; ?>>Female</option>
                                                                <option value="Other" <?php if ($GENDER == "Other") echo 'selected = "selected"'; ?>>Other</option>
                                                            </select>
                                                        </div>

                                                        <!-- Date of Birth -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Date of Birth</label>
                                                            <input type="text" class="form-control-modern datepicker-past" id="DOB" name="DOB" value="<?= ($DOB) ? date('m/d/Y', strtotime($DOB)) : '' ?>" placeholder="mm/dd/yyyy">
                                                        </div>

                                                        <!-- Address -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Address</label>
                                                            <input type="text" id="ADDRESS" name="ADDRESS" class="form-control-modern" placeholder="Enter Address" value="<?php echo htmlspecialchars($ADDRESS) ?>">
                                                        </div>

                                                        <!-- Apt/Ste -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Apt/Ste</label>
                                                            <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control-modern" placeholder="Enter Apartment or Suite" value="<?php echo htmlspecialchars($ADDRESS_1) ?>">
                                                        </div>

                                                        <!-- Country -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Country <span class="required">*</span></label>
                                                            <select class="form-control-modern" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)" required>
                                                                <option value="">Select Country</option>
                                                                <?php
                                                                $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                                while (!$row->EOF) { ?>
                                                                    <option value="<?php echo $row->fields['PK_COUNTRY']; ?>" <?= ($row->fields['PK_COUNTRY'] == $PK_COUNTRY) ? "selected" : "" ?>><?= htmlspecialchars($row->fields['COUNTRY_NAME']) ?></option>
                                                                <?php $row->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>

                                                        <!-- State -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">State <span class="required">*</span></label>
                                                            <div id="State_div"></div>
                                                        </div>

                                                        <!-- City -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">City</label>
                                                            <input type="text" id="CITY" name="CITY" class="form-control-modern" placeholder="Enter your city" value="<?php echo htmlspecialchars($CITY) ?>">
                                                        </div>

                                                        <!-- Zip -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Postal / Zip Code</label>
                                                            <input type="text" id="ZIP" name="ZIP" class="form-control-modern" placeholder="Enter Postal / Zip Code" value="<?php echo htmlspecialchars($ZIP) ?>">
                                                        </div>

                                                        <!-- Location -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Location <span class="required">*</span>
                                                                <span class="tooltip-bubble" tabindex="0">
                                                                    <i class="ti-help-alt" aria-hidden="true"></i>
                                                                    <span class="tooltip-text">Primary Location access</span>
                                                                </span>
                                                            </label>
                                                            <select class="multi_sumo_select_location" name="PK_USER_LOCATION[]" id="PK_LOCATION_MULTIPLE" multiple required>
                                                                <?php
                                                                $selected_location = [];
                                                                if (!empty($_GET['id'])) {
                                                                    $selected_location_row = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` WHERE `PK_USER` = '$_GET[id]'");
                                                                    while (!$selected_location_row->EOF) {
                                                                        $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                                                                        $selected_location_row->MoveNext();
                                                                    }
                                                                }
                                                                $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                                while (!$row->EOF) { ?>
                                                                    <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= in_array($row->fields['PK_LOCATION'], $selected_location) ? "selected" : "" ?>><?= htmlspecialchars($row->fields['LOCATION_NAME']) ?></option>
                                                                <?php $row->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>

                                                        <!-- Remarks -->
                                                        <div class="form-group-modern full-width">
                                                            <label class="form-label">Remarks</label>
                                                            <textarea class="form-control-modern" rows="3" id="NOTES" name="NOTES" placeholder="Enter remarks"><?php echo htmlspecialchars($NOTES) ?></textarea>
                                                        </div>

                                                        <!-- User Image -->
                                                        <div class="form-group-modern full-width">
                                                            <label class="form-label">Users / Employees / Service Providers Photo</label>
                                                            <input type="file" name="USER_IMAGE" id="USER_IMAGE" class="form-control-modern">
                                                            <?php if ($USER_IMAGE != '') { ?>
                                                                <div class="image-preview">
                                                                    <a class="fancybox" href="<?php echo htmlspecialchars($USER_IMAGE); ?>" data-fancybox-group="gallery">
                                                                        <img src="<?php echo htmlspecialchars($USER_IMAGE); ?>" alt="User Image">
                                                                    </a>
                                                                </div>
                                                            <?php } ?>
                                                        </div>

                                                        <!-- Active -->
                                                        <?php if (!empty($_GET['id'])) { ?>
                                                            <div class="form-group-modern full-width <?= ($INACTIVE_BY_ADMIN == 1) ? 'div_inactive' : '' ?>">
                                                                <label class="form-label">Active</label>
                                                                <div class="radio-group-modern">
                                                                    <label class="radio-item">
                                                                        <input type="radio" name="ACTIVE" id="ACTIVE_YES" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?>> Active
                                                                    </label>
                                                                    <label class="radio-item">
                                                                        <input type="radio" name="ACTIVE" id="ACTIVE_NO" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?>> Inactive
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                    </div>

                                                    <div class="form-actions">
                                                        <button type="submit" class="btn-modern btn-modern-primary">
                                                            <i class="fas fa-save"></i>
                                                            <?= empty($_GET['id']) ? 'Continue' : 'Save' ?>
                                                        </button>
                                                        <button type="button" id="cancel_button" class="btn-modern btn-modern-secondary">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>

                                            <!-- Login Tab -->
                                            <div class="tab-pane-modern" id="login" role="tabpanel">
                                                <form class="form-material form-horizontal" id="login_form">
                                                    <input type="hidden" name="FUNCTION_NAME" value="saveLoginData">
                                                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                                    <input type="hidden" class="TYPE" name="TYPE" value="1">

                                                    <div class="form-grid">
                                                        <!-- User Email -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">User Email <span class="required">*</span></label>
                                                            <input type="text" id="LOGIN_EMAIL_ID" name="EMAIL_ID" class="form-control-modern" placeholder="Enter Email" value="<?= htmlspecialchars($EMAIL_ID) ?>" required readonly>
                                                            <div id="uname_result" class="form-helper"></div>
                                                            <span id="lblError" style="color: red"></span>
                                                        </div>

                                                        <!-- Can Edit Enrollment -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Can Edit Enrollment
                                                                <span class="tooltip-bubble" tabindex="0">
                                                                    <i class="ti-help-alt" aria-hidden="true"></i>
                                                                    <span class="tooltip-text">Allows this User to Edit some fields on Enrollments</span>
                                                                </span>
                                                            </label>
                                                            <div class="radio-group-modern">
                                                                <label class="radio-item">
                                                                    <input type="radio" name="CAN_EDIT_ENROLLMENT" id="CAN_EDIT_ENROLLMENT_YES" value="1" <?php if ($CAN_EDIT_ENROLLMENT == 1) echo 'checked="checked"'; ?>> Yes
                                                                </label>
                                                                <label class="radio-item">
                                                                    <input type="radio" name="CAN_EDIT_ENROLLMENT" id="CAN_EDIT_ENROLLMENT_NO" value="0" <?php if ($CAN_EDIT_ENROLLMENT == 0) echo 'checked="checked"'; ?>> No
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <!-- Can Create Support Tickets -->
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Can Create Support Tickets
                                                                <span class="tooltip-bubble" tabindex="0">
                                                                    <i class="ti-help-alt" aria-hidden="true"></i>
                                                                    <span class="tooltip-text">Allows this User to send System Errors to Doable Team</span>
                                                                </span>
                                                            </label>
                                                            <div class="radio-group-modern">
                                                                <label class="radio-item">
                                                                    <input type="radio" name="TICKET_SYSTEM_ACCESS" id="TICKET_SYSTEM_ACCESS_YES" value="1" <?php if ($TICKET_SYSTEM_ACCESS == 1) echo 'checked="checked"'; ?>> Yes
                                                                </label>
                                                                <label class="radio-item">
                                                                    <input type="radio" name="TICKET_SYSTEM_ACCESS" id="TICKET_SYSTEM_ACCESS_NO" value="0" <?php if ($TICKET_SYSTEM_ACCESS == 0) echo 'checked="checked"'; ?>> No
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Password Section -->
                                                    <?php if (empty($_GET['id']) || $PASSWORD == '') { ?>
                                                        <div class="form-grid" style="margin-top: 16px;">
                                                            <div class="form-group-modern">
                                                                <label class="form-label">Password</label>
                                                                <div style="display: flex; gap: 8px;">
                                                                    <input type="password" class="form-control-modern" placeholder="Password" name="PASSWORD" id="PASSWORD" onkeyup="isGood(this.value)" style="flex: 1;">
                                                                    <button type="button" class="btn-modern btn-modern-secondary btn-modern-sm" onclick="togglePasswordVisibility()" style="padding: 10px 14px;">
                                                                        <i class="fas fa-eye" id="password_eye_icon"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="form-group-modern">
                                                                <label class="form-label">Confirm Password</label>
                                                                <div style="display: flex; gap: 8px;">
                                                                    <input type="password" class="form-control-modern" placeholder="Confirm Password" name="CONFIRM_PASSWORD" id="CONFIRM_PASSWORD" onkeyup="isGood(this.value)" style="flex: 1;">
                                                                    <button type="button" class="btn-modern btn-modern-secondary btn-modern-sm" onclick="toggleConfirmPasswordVisibility()" style="padding: 10px 14px;">
                                                                        <i class="fas fa-eye" id="confirm_password_eye_icon"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div id="password_error" style="color: red; margin-top: 8px;"></div>

                                                        <div style="margin-top: 8px;">
                                                            <span style="color: orange; font-size: 13px;">Note: Password must contain at least one number, one uppercase and lowercase letter, and at least 8 characters</span>
                                                        </div>

                                                        <div class="password-strength" style="margin-top: 8px;">
                                                            <span>Password Strength:</span>
                                                            <span id="password-text"></span>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div id="change_password_div" style="display: none; margin-top: 16px;">
                                                            <div class="form-grid">
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">New Password</label>
                                                                    <div style="display: flex; gap: 8px;">
                                                                        <input type="password" name="PASSWORD" class="form-control-modern" id="PASSWORD" style="flex: 1;">
                                                                        <button type="button" class="btn-modern btn-modern-secondary btn-modern-sm" onclick="togglePasswordVisibility()" style="padding: 10px 14px;">
                                                                            <i class="fas fa-eye" id="password_eye_icon"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Confirm New Password</label>
                                                                    <div style="display: flex; gap: 8px;">
                                                                        <input type="password" name="CONFIRM_PASSWORD" class="form-control-modern" id="CONFIRM_PASSWORD" style="flex: 1;">
                                                                        <button type="button" class="btn-modern btn-modern-secondary btn-modern-sm" onclick="toggleConfirmPasswordVisibility()" style="padding: 10px 14px;">
                                                                            <i class="fas fa-eye" id="confirm_password_eye_icon"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div id="password_error" style="color: red; margin-top: 8px;"></div>
                                                        </div>

                                                        <div style="margin-top: 12px;">
                                                            <button type="button" class="btn-modern btn-modern-secondary btn-modern-sm" onclick="$('#change_password_div').slideToggle();">
                                                                <i class="fas fa-key"></i> Change Password
                                                            </button>
                                                        </div>
                                                    <?php } ?>

                                                    <div class="form-actions">
                                                        <button type="submit" class="btn-modern btn-modern-primary">
                                                            <i class="fas fa-save"></i>
                                                            <?= empty($_GET['id']) ? 'Continue' : 'Save' ?>
                                                        </button>
                                                        <button type="button" id="cancel_button" class="btn-modern btn-modern-secondary">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>

                                            <!-- Rates Tab -->
                                            <div class="tab-pane-modern" id="rates" role="tabpanel">
                                                <form id="engagement_form">
                                                    <input type="hidden" name="FUNCTION_NAME" value="saveEngagementData">
                                                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                                    <input type="hidden" class="TYPE" name="TYPE" value="3">

                                                    <h4 style="font-size: 16px; font-weight: 600; color: var(--gray-700); margin-bottom: 16px;">Engagement Terms</h4>

                                                    <?php
                                                    $i = 0;
                                                    if (!empty($_GET['id'])) {
                                                        $row = $db->Execute("SELECT DOA_RATE_TYPE.PK_RATE_TYPE, DOA_RATE_TYPE.RATE_NAME, DOA_RATE_TYPE.PRICE_TYPE, DOA_USER_RATE.RATE, DOA_USER_RATE.ACTIVE FROM DOA_RATE_TYPE LEFT JOIN $account_database.DOA_USER_RATE AS DOA_USER_RATE ON DOA_RATE_TYPE.PK_RATE_TYPE = DOA_USER_RATE.PK_RATE_TYPE WHERE DOA_RATE_TYPE.ACTIVE = 1 AND DOA_USER_RATE.PK_USER = '$_GET[id]' ORDER BY DOA_RATE_TYPE.PK_RATE_TYPE ASC");
                                                        if ($row->RecordCount() > 0) {
                                                            while (!$row->EOF) { ?>
                                                                <div class="rate-row">
                                                                    <span class="rate-label"><?= htmlspecialchars($row->fields['RATE_NAME']) ?></span>
                                                                    <input type="hidden" name="PK_RATE_TYPE[]" value="<?= $row->fields['PK_RATE_TYPE'] ?>">
                                                                    <label class="checkbox-group-modern" style="justify-self: start;">
                                                                        <input type="checkbox" class="engagement_terms" name="PK_RATE_TYPE_ACTIVE[<?= $i ?>]" id="<?= $row->fields['PK_RATE_TYPE'] ?>" value="1" <?= (is_null($row->fields['ACTIVE']) || $row->fields['ACTIVE'] == 0) ? '' : 'checked' ?>>
                                                                        <span>Enable</span>
                                                                    </label>
                                                                    <div style="display: <?= (is_null($row->fields['ACTIVE']) || $row->fields['ACTIVE'] == 0) ? 'none' : '' ?>;">
                                                                        <div class="input-group-modern" style="max-width: 200px;">
                                                                            <?php if ($row->fields['PRICE_TYPE'] == 1) { ?>
                                                                                <span class="input-group-text"><?= $currency ?></span>
                                                                            <?php } else { ?>
                                                                                <span class="input-group-text">%</span>
                                                                            <?php } ?>
                                                                            <input type="text" class="form-control-modern" oninput="setFormat(this)" name="RATE[]" value="<?= htmlspecialchars($row->fields['RATE']) ?>" style="text-align: right;">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php
                                                                $i++;
                                                                $row->MoveNext();
                                                            }
                                                        } else {
                                                            $row = $db->Execute("SELECT * FROM DOA_RATE_TYPE WHERE ACTIVE = 1 ORDER BY PK_RATE_TYPE ASC");
                                                            while (!$row->EOF) { ?>
                                                                <div class="rate-row">
                                                                    <span class="rate-label"><?= htmlspecialchars($row->fields['RATE_NAME']) ?></span>
                                                                    <input type="hidden" name="PK_RATE_TYPE[]" value="<?= $row->fields['PK_RATE_TYPE'] ?>">
                                                                    <label class="checkbox-group-modern" style="justify-self: start;">
                                                                        <input type="checkbox" class="engagement_terms" oninput="setFormat(this)" name="PK_RATE_TYPE_ACTIVE[<?= $i ?>]" id="<?= $row->fields['PK_RATE_TYPE'] ?>" value="1">
                                                                        <span>Enable</span>
                                                                    </label>
                                                                    <div style="display: <?= (is_null($row->fields['ACTIVE']) || $row->fields['ACTIVE'] == 0) ? 'none' : '' ?>;">
                                                                        <div class="input-group-modern" style="max-width: 200px;">
                                                                            <?php if ($row->fields['PRICE_TYPE'] == 1) { ?>
                                                                                <span class="input-group-text"><?= $currency ?></span>
                                                                            <?php } else { ?>
                                                                                <span class="input-group-text">%</span>
                                                                            <?php } ?>
                                                                            <input type="text" class="form-control-modern" name="RATE[]" style="text-align: right;">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php
                                                                $i++;
                                                                $row->MoveNext();
                                                            }
                                                        }
                                                    } else {
                                                        $row = $db->Execute("SELECT * FROM DOA_RATE_TYPE WHERE ACTIVE = 1 ORDER BY PK_RATE_TYPE ASC");
                                                        while (!$row->EOF) { ?>
                                                            <div class="rate-row">
                                                                <span class="rate-label"><?= htmlspecialchars($row->fields['RATE_NAME']) ?></span>
                                                                <input type="hidden" name="PK_RATE_TYPE[]" value="<?= $row->fields['PK_RATE_TYPE'] ?>">
                                                                <label class="checkbox-group-modern" style="justify-self: start;">
                                                                    <input type="checkbox" class="engagement_terms" oninput="setFormat(this)" name="PK_RATE_TYPE_ACTIVE[<?= $i ?>]" id="<?= $row->fields['PK_RATE_TYPE'] ?>" value="1">
                                                                    <span>Enable</span>
                                                                </label>
                                                                <div style="display: none;">
                                                                    <div class="input-group-modern" style="max-width: 200px;">
                                                                        <?php if ($row->fields['PRICE_TYPE'] == 1) { ?>
                                                                            <span class="input-group-text"><?= $currency ?></span>
                                                                        <?php } else { ?>
                                                                            <span class="input-group-text">%</span>
                                                                        <?php } ?>
                                                                        <input type="text" class="form-control-modern" name="RATE[]" style="text-align: right;">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                    <?php
                                                            $i++;
                                                            $row->MoveNext();
                                                        }
                                                    } ?>

                                                    <h4 style="font-size: 16px; font-weight: 600; color: var(--gray-700); margin: 24px 0 16px 0;">Service Based Terms</h4>

                                                    <div style="margin-bottom: 12px;">
                                                        <button type="button" class="btn-modern btn-modern-secondary btn-modern-sm" onclick="addMoreCodeCommission()">
                                                            <i class="fas fa-plus"></i> New
                                                        </button>
                                                    </div>

                                                    <div id="commission_container">
                                                        <?php
                                                        if (!empty($_GET['id'])) {
                                                            $code_commission_data = $db_account->Execute("SELECT * FROM DOA_SERVICE_COMMISSION WHERE PK_USER = " . $_GET['id']);
                                                            while (!$code_commission_data->EOF) { ?>
                                                                <div class="commission-row">
                                                                    <select class="form-control-modern" name="PK_SERVICE_MASTER[]">
                                                                        <option value="">Select Service</option>
                                                                        <?php
                                                                        $row = $db_account->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE ACTIVE = 1 AND IS_DELETED = 0");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>" <?= ($code_commission_data->fields['PK_SERVICE_MASTER'] == $row->fields['PK_SERVICE_MASTER']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['SERVICE_NAME']) ?></option>
                                                                        <?php $row->MoveNext();
                                                                        } ?>
                                                                    </select>
                                                                    <input type="text" class="form-control-modern" name="COMMISSION_AMOUNT[]" placeholder="Amount" value="<?= htmlspecialchars($code_commission_data->fields['COMMISSION_AMOUNT']) ?>">
                                                                    <button type="button" class="btn-modern btn-modern-danger btn-modern-sm" onclick="removeThis(this);" style="padding: 8px 12px;">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            <?php $code_commission_data->MoveNext();
                                                            } ?>
                                                        <?php } else { ?>
                                                            <div class="commission-row">
                                                                <select class="form-control-modern" name="PK_SERVICE_MASTER[]">
                                                                    <option value="">Select Service</option>
                                                                    <?php
                                                                    $row = $db_account->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE ACTIVE = 1 AND IS_DELETED = 0");
                                                                    while (!$row->EOF) { ?>
                                                                        <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>"><?= htmlspecialchars($row->fields['SERVICE_NAME']) ?></option>
                                                                    <?php $row->MoveNext();
                                                                    } ?>
                                                                </select>
                                                                <input type="text" class="form-control-modern" name="COMMISSION_AMOUNT[]" placeholder="Amount">
                                                                <button type="button" class="btn-modern btn-modern-danger btn-modern-sm" onclick="removeThis(this);" style="padding: 8px 12px;">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        <?php } ?>
                                                        <div id="add_more_code_commission"></div>
                                                    </div>

                                                    <div class="form-actions">
                                                        <button type="submit" class="btn-modern btn-modern-primary">
                                                            <i class="fas fa-save"></i>
                                                            <?= empty($_GET['id']) ? 'Continue' : 'Save' ?>
                                                        </button>
                                                        <button type="button" id="cancel_button" class="btn-modern btn-modern-secondary">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>

                                            <!-- Service Tab -->
                                            <div class="tab-pane-modern" id="service" role="tabpanel">
                                                <form id="location_hour_form">
                                                    <input type="hidden" name="FUNCTION_NAME" value="saveLocationHourData">
                                                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                                    <input type="hidden" class="TYPE" name="TYPE" value="3">

                                                    <div id="location_details_div"></div>

                                                    <div class="form-actions">
                                                        <button type="submit" class="btn-modern btn-modern-primary">
                                                            <i class="fas fa-save"></i>
                                                            <?= empty($_GET['id']) ? 'Continue' : 'Save' ?>
                                                        </button>
                                                        <button type="button" id="cancel_button" class="btn-modern btn-modern-secondary">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>

                                            <!-- Documents Tab -->
                                            <div class="tab-pane-modern" id="documents" role="tabpanel">
                                                <form id="document_form">
                                                    <input type="hidden" name="FUNCTION_NAME" value="saveUserDocumentData">
                                                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                                    <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?= $PK_CUSTOMER_DETAILS ?>">
                                                    <input type="hidden" class="TYPE" name="TYPE" value="2">

                                                    <div id="append_user_document">
                                                        <?php
                                                        if (!empty($_GET['id'])) {
                                                            $user_doc_count = 0;
                                                            $row = $db_account->Execute("SELECT * FROM DOA_USER_DOCUMENT WHERE PK_USER = '$PK_USER'");
                                                            while (!$row->EOF) { ?>
                                                                <div class="document-row">
                                                                    <div class="form-group-modern">
                                                                        <label class="form-label">Document Name</label>
                                                                        <input type="text" name="DOCUMENT_NAME[]" class="form-control-modern" placeholder="Enter Document Name" value="<?= htmlspecialchars($row->fields['DOCUMENT_NAME']) ?>">
                                                                    </div>
                                                                    <div class="form-group-modern">
                                                                        <label class="form-label">Document File</label>
                                                                        <input type="file" name="FILE_PATH[]" class="form-control-modern">
                                                                        <?php if (!empty($row->fields['FILE_PATH'])): ?>
                                                                            <a target="_blank" href="<?= htmlspecialchars($row->fields['FILE_PATH']) ?>" style="color: var(--primary-color); font-size: 13px; margin-top: 4px; display: inline-block;">View Current File</a>
                                                                        <?php endif; ?>
                                                                        <input type="hidden" name="FILE_PATH_URL[]" value="<?= htmlspecialchars($row->fields['FILE_PATH']) ?>">
                                                                    </div>
                                                                    <button type="button" class="btn-modern btn-modern-danger btn-modern-sm" onclick="removeUserDocument(this);" style="padding: 8px 12px; margin-bottom: 2px;">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </div>
                                                            <?php $row->MoveNext();
                                                                $user_doc_count++;
                                                            } ?>
                                                        <?php } else {
                                                            $user_doc_count = 1; ?>
                                                            <div class="document-row">
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Document Name</label>
                                                                    <input type="text" name="DOCUMENT_NAME[]" class="form-control-modern" placeholder="Enter Document Name">
                                                                </div>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Document File</label>
                                                                    <input type="file" name="FILE_PATH[]" class="form-control-modern">
                                                                </div>
                                                                <button type="button" class="btn-modern btn-modern-danger btn-modern-sm" onclick="removeUserDocument(this);" style="padding: 8px 12px; margin-bottom: 2px;">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        <?php } ?>
                                                    </div>

                                                    <div style="margin: 16px 0;">
                                                        <button type="button" class="btn-modern btn-modern-secondary btn-modern-sm" onclick="addMoreUserDocument();">
                                                            <i class="fas fa-plus"></i> New
                                                        </button>
                                                    </div>

                                                    <div class="form-actions">
                                                        <button type="submit" class="btn-modern btn-modern-primary">
                                                            <i class="fas fa-save"></i>
                                                            <?= empty($_GET['id']) ? 'Continue' : 'Save' ?>
                                                        </button>
                                                        <button type="button" id="cancel_button" class="btn-modern btn-modern-secondary">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>

                                            <!-- Comments Tab -->
                                            <div class="tab-pane-modern" id="comments" role="tabpanel">
                                                <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
                                                    <button type="button" class="btn-modern btn-modern-primary" onclick="createUserComment();">
                                                        <i class="fas fa-plus-circle"></i> Create New
                                                    </button>
                                                </div>

                                                <div style="overflow-x: auto;">
                                                    <table class="table-modern" id="myTable">
                                                        <thead>
                                                            <tr>
                                                                <th>Commented Date</th>
                                                                <th>Commented User</th>
                                                                <th>Comment</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $comment_data = $db_account->Execute("SELECT DOA_COMMENT.PK_COMMENT, DOA_COMMENT.COMMENT, DOA_COMMENT.COMMENT_DATE, DOA_COMMENT.ACTIVE, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS FULL_NAME FROM `DOA_COMMENT` INNER JOIN DOA_USERS ON DOA_COMMENT.BY_PK_USER = DOA_USERS.PK_USER WHERE `FOR_PK_USER` = " . $PK_USER);
                                                            while (!$comment_data->EOF) { ?>
                                                                <tr>
                                                                    <td onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);" class="cursor-pointer"><?= date('m/d/Y', strtotime($comment_data->fields['COMMENT_DATE'])) ?></td>
                                                                    <td onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);" class="cursor-pointer"><?= htmlspecialchars($comment_data->fields['FULL_NAME']) ?></td>
                                                                    <td onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);" class="cursor-pointer"><?= htmlspecialchars($comment_data->fields['COMMENT']) ?></td>
                                                                    <td>
                                                                        <div class="action-icons">
                                                                            <a href="javascript:;" onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);" title="Edit"><i class="fas fa-edit" style="font-size: 18px;"></i></a>
                                                                            <a href="javascript:;" onclick="deleteComment(<?= $comment_data->fields['PK_COMMENT'] ?>);" title="Delete" style="color: var(--danger-color);"><i class="fas fa-trash" style="font-size: 18px;"></i></a>
                                                                            <?php if ($comment_data->fields['ACTIVE'] == 1) { ?>
                                                                                <span class="active-box-green" title="Active"></span>
                                                                            <?php } else { ?>
                                                                                <span class="active-box-red" title="Inactive"></span>
                                                                            <?php } ?>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            <?php $comment_data->MoveNext();
                                                            } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Comment Modal -->
    <div id="commentModel" class="modal-modern">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="comment_header">Add Comment</h4>
                <button class="close close_comment_model">&times;</button>
            </div>
            <div class="modal-body">
                <form id="comment_add_edit_form" role="form" action="" method="post">
                    <input type="hidden" name="FUNCTION_NAME" value="saveCommentData">
                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                    <input type="hidden" name="PK_COMMENT" id="PK_COMMENT" value="0">

                    <div class="form-grid">
                        <div class="form-group-modern full-width">
                            <label class="form-label">Comments <span class="required">*</span></label>
                            <textarea class="form-control-modern" rows="8" name="COMMENT" id="COMMENT" required placeholder="Enter your comment here..."></textarea>
                        </div>

                        <div class="form-group-modern">
                            <label class="form-label">Date <span class="required">*</span></label>
                            <input type="date" class="form-control-modern" name="COMMENT_DATE" id="COMMENT_DATE" required>
                        </div>

                        <div class="form-group-modern" id="comment_active" style="display: none;">
                            <label class="form-label">Active</label>
                            <div class="radio-group-modern">
                                <label class="radio-item">
                                    <input type="radio" id="COMMENT_ACTIVE_1" name="ACTIVE" value="1"> Active
                                </label>
                                <label class="radio-item">
                                    <input type="radio" id="COMMENT_ACTIVE_0" name="ACTIVE" value="0"> Inactive
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions" style="border-top: none; margin-top: 0; padding-top: 0;">
                        <button type="submit" class="btn-modern btn-modern-primary">
                            <i class="fas fa-save"></i> Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>
    <script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>

    <script>
        // Tab switching
        document.querySelectorAll('.tab-item').forEach(tab => {
            tab.addEventListener('click', function() {
                if (this.classList.contains('disabled')) return;
                document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-pane-modern').forEach(p => p.classList.remove('active'));

                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });

        $('.datepicker-past').datepicker({
            format: 'mm/dd/yyyy',
            maxDate: 0,
            changeMonth: true,
            changeYear: true,
            yearRange: '1900:' + new Date().getFullYear(),
        });

        $('#user_selector').SumoSelect({
            placeholder: 'Select User',
            search: true,
            searchText: 'Search...'
        });

        $('.multi_sumo_select_location').SumoSelect({
            placeholder: 'Select Location',
            selectAll: true
        });

        $('.multi_sumo_select_roles').SumoSelect({
            placeholder: 'Select Roles',
            selectAll: true
        });

        $('.multi_sumo_select_services').SumoSelect({
            placeholder: 'Select Services',
            selectAll: true
        });

        function editpage(param) {
            var id = $(param).val();
            if (id) {
                window.location.href = "user.php?id=" + id;
            }
        }

        $(document).ready(function() {
            fetch_state(<?php echo $PK_COUNTRY; ?>);
        });

        function fetch_state(PK_COUNTRY) {
            jQuery(document).ready(function() {
                let data = "PK_COUNTRY=" + PK_COUNTRY + "&PK_STATES=<?= $PK_STATES; ?>";
                let value = $.ajax({
                    url: "ajax/state.php",
                    type: "POST",
                    data: data,
                    async: false,
                    cache: false,
                    success: function(result) {
                        document.getElementById('State_div').innerHTML = result;
                    }
                }).responseText;
            });
        }

        function setFormat(param) {
            if ($(param).val() != "") {
                $(param).val(parseFloat($(param).val().replace(/,/g, ""))
                    .toString()
                    .replace(/\B(?=(\d{3})+(?!\d))/g, ","));
            }
        }

        $(document).on('focus', '.time-picker', function() {
            let minTime = $(this).closest('.form-group').find('.minTime').val();
            let maxTime = $(this).closest('.form-group').find('.maxTime').val();
            $(this).timepicker({
                timeFormat: 'hh:mm p',
                interval: 30,
                dynamic: false,
                dropdown: true,
                scrollbar: true,
                minTime: minTime,
                maxTime: maxTime
            });
        });

        function closeThisDay(param) {
            if ($(param).is(':checked')) {
                $(param).closest('.row').find('.time-input').val('');
                $(param).closest('.row').find('.time-input').each(function() {
                    this.style.cssText = 'background-color: #80808080 !important; pointer-events: none !important;';
                });
            } else {
                $(param).closest('.row').find('.time-input').css('pointer-events', '');
                $(param).closest('.row').find('.time-input').css('background-color', '');
            }
        }

        let PK_USER = parseInt(<?= empty($_GET['id']) ? 0 : $_GET['id'] ?>);
        let NEW_USER = parseInt(<?= empty($_GET['id']) ? true : false ?>);

        function togglePasswordVisibility() {
            let passwordInput = document.getElementById("PASSWORD");
            let eyeIcon = document.getElementById("password_eye_icon");
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                if (eyeIcon) eyeIcon.className = "fas fa-eye-slash";
            } else {
                passwordInput.type = "password";
                if (eyeIcon) eyeIcon.className = "fas fa-eye";
            }
        }

        function toggleConfirmPasswordVisibility() {
            let passwordInput = document.getElementById("CONFIRM_PASSWORD");
            let eyeIcon = document.getElementById("confirm_password_eye_icon");
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                if (eyeIcon) eyeIcon.className = "fas fa-eye-slash";
            } else {
                passwordInput.type = "password";
                if (eyeIcon) eyeIcon.className = "fas fa-eye";
            }
        }

        function isGood(password) {
            let password_strength = document.getElementById("password-text");

            if (password.length == 0) {
                password_strength.innerHTML = "";
                return;
            }
            let regex = new Array();
            regex.push("[A-Z]");
            regex.push("[a-z]");
            regex.push("[0-9]");
            regex.push("[$@$!%*#?&]");
            let passed = 0;
            for (let i = 0; i < regex.length; i++) {
                if (new RegExp(regex[i]).test(password)) {
                    passed++;
                }
            }
            let strength = "";
            switch (passed) {
                case 0:
                case 1:
                case 2:
                    strength = "<small class='progress-bar bg-danger' style='width: 50%; display: inline-block;'>Weak</small>";
                    break;
                case 3:
                    strength = "<small class='progress-bar bg-warning' style='width: 60%; display: inline-block;'>Medium</small>";
                    break;
                case 4:
                    strength = "<small class='progress-bar bg-success' style='width: 100%; display: inline-block;'>Strong</small>";
                    break;
            }
            password_strength.innerHTML = strength;
        }

        $(document).on('click', '#cancel_button', function() {
            window.location.href = 'all_users.php';
        });

        function createLogin(param) {
            if ($(param).is(':checked')) {
                $('#login_tab_btn').show();
                $('#PHONE').prop('required', true);
                $('#EMAIL_ID').prop('required', true);
            } else {
                $('#login_tab_btn').hide();
                $('#PHONE').prop('required', false);
                $('#EMAIL_ID').prop('required', false);
            }
        }

        let counter = parseInt(<?= $user_doc_count ?>);

        function addMoreUserDocument() {
            $('#append_user_document').append(`<div class="document-row">
                <div class="form-group-modern">
                    <label class="form-label">Document Name</label>
                    <input type="text" name="DOCUMENT_NAME[]" class="form-control-modern" placeholder="Enter Document Name">
                </div>
                <div class="form-group-modern">
                    <label class="form-label">Document File</label>
                    <input type="file" name="FILE_PATH[]" class="form-control-modern">
                </div>
                <button type="button" class="btn-modern btn-modern-danger btn-modern-sm" onclick="removeUserDocument(this);" style="padding: 8px 12px; margin-bottom: 2px;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>`);
            counter++;
        }

        function removeUserDocument(param) {
            $(param).closest('.document-row').remove();
            counter--;
        }

        $(document).ready(function() {
            let tab_link = <?= empty($_GET['tab']) ? 0 : $_GET['tab'] ?>;
            if (tab_link == 'profile') {
                document.querySelector('[data-tab="profile"]')?.click();
            }
            if (tab_link == 'login') {
                document.querySelector('[data-tab="login"]')?.click();
            }
            if (tab_link == 'rates') {
                document.querySelector('[data-tab="rates"]')?.click();
            }
            if (tab_link == 'service') {
                document.querySelector('[data-tab="service"]')?.click();
            }
            if (tab_link == 'documents') {
                document.querySelector('[data-tab="documents"]')?.click();
            }
            if (tab_link == 'comments') {
                document.querySelector('[data-tab="comments"]')?.click();
            }
        });

        $(document).on('submit', '#profile_form', function(event) {
            event.preventDefault();
            let PK_USER = $('.PK_USER').val();
            const PHONE = $('#PHONE').val().trim();
            const EMAIL_ID = $('#EMAIL_ID').val().trim();
            if (PHONE != '') {
                $.ajax({
                    url: 'ajax/username_checker.php',
                    type: 'post',
                    data: {
                        PHONE: PHONE
                    },
                    success: function(response) {
                        if (response && PK_USER == 0) {
                            $('#phone_result').html(response);
                        } else {
                            $('#phone_result').html('');
                            if (EMAIL_ID != '') {
                                $.ajax({
                                    url: 'ajax/username_checker.php',
                                    type: 'post',
                                    data: {
                                        EMAIL_ID: EMAIL_ID
                                    },
                                    success: function(response) {
                                        if (response && PK_USER == 0) {
                                            $('#email_result').html(response);
                                        } else {
                                            $('#email_result').html('');
                                            let form_data = new FormData($('#profile_form')[0]);
                                            $.ajax({
                                                url: "ajax/AjaxFunctions.php",
                                                type: 'POST',
                                                data: form_data,
                                                processData: false,
                                                contentType: false,
                                                dataType: 'JSON',
                                                success: function(data) {
                                                    $('.PK_USER').val(data.PK_USER);
                                                    $('.PK_CUSTOMER_DETAILS').val(data.PK_CUSTOMER_DETAILS);
                                                    if (PK_USER == 0 || NEW_USER) {
                                                        if ($('#CREATE_LOGIN').is(':checked')) {
                                                            $('#LOGIN_EMAIL_ID').val(EMAIL_ID);
                                                            document.querySelector('[data-tab="login"]')?.click();
                                                        } else {
                                                            if ($('#PK_ROLES').val().indexOf('5') !== -1) {
                                                                document.querySelector('[data-tab="rates"]')?.click();
                                                            } else {
                                                                document.querySelector('[data-tab="documents"]')?.click();
                                                            }
                                                        }
                                                    } else {
                                                        window.location.href = 'user.php?tab=profile&id=' + data.PK_USER;
                                                    }
                                                }
                                            });
                                        }
                                    }
                                });
                            }
                        }
                    }
                });
            }
        });

        function goToLoginTab() {
            let PK_USER = $('.PK_USER').val();
            if (!PK_USER) {
                alert('Please fill up the profile and click next.');
                document.querySelector('[data-tab="profile"]')?.click();
            }
        }

        $(document).on('submit', '#login_form', function(event) {
            event.preventDefault();
            let PK_USER = $('.PK_USER').val();
            let PASSWORD = $('#PASSWORD').val();
            let CONFIRM_PASSWORD = $('#CONFIRM_PASSWORD').val();
            if (PASSWORD === CONFIRM_PASSWORD) {
                let form_data = $('#login_form').serialize();
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: form_data,
                    success: function(data) {
                        if (PK_USER == 0 || NEW_USER) {
                            if ($('#PK_ROLES').val().indexOf('5') !== -1) {
                                document.querySelector('[data-tab="rates"]')?.click();
                            } else {
                                document.querySelector('[data-tab="documents"]')?.click();
                            }
                        } else {
                            window.location.href = 'user.php?tab=login&id=' + PK_USER;
                        }
                    }
                });
            } else {
                $('#password_error').text('Password and Confirm Password not matched');
            }
        });

        function addMoreCodeCommission() {
            $('#add_more_code_commission').append(`<div class="commission-row">
                <select class="form-control-modern" name="PK_SERVICE_MASTER[]">
                    <option value="">Select Service</option>
                    <?php
                    $row = $db_account->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE ACTIVE = 1 AND IS_DELETED = 0");
                    while (!$row->EOF) { ?>
                        <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>"><?= htmlspecialchars($row->fields['SERVICE_NAME']) ?></option>
                    <?php $row->MoveNext();
                    } ?>
                </select>
                <input type="text" class="form-control-modern" name="COMMISSION_AMOUNT[]" placeholder="Amount">
                <button type="button" class="btn-modern btn-modern-danger btn-modern-sm" onclick="removeThis(this);" style="padding: 8px 12px;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>`);
        }

        function removeThis(param) {
            $(param).closest('.commission-row').remove();
        }

        $(document).on('submit', '#engagement_form', function(event) {
            event.preventDefault();
            let PK_USER = $('.PK_USER').val();
            let form_data = $('#engagement_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success: function(data) {
                    if (PK_USER == 0 || NEW_USER) {
                        if ($('#PK_ROLES').val().indexOf('5') !== -1) {
                            document.querySelector('[data-tab="service"]')?.click();
                        } else {
                            document.querySelector('[data-tab="documents"]')?.click();
                        }
                    } else {
                        window.location.href = 'user.php?tab=rates&id=' + PK_USER;
                    }
                }
            });
        });

        function getLocationHours() {
            let PK_USER = $('.PK_USER').val();
            $.ajax({
                url: "ajax/get_location_hours.php",
                type: 'POST',
                data: {
                    PK_USER: PK_USER
                },
                success: function(data) {
                    $('#location_details_div').html(data);
                }
            });
        }

        $(document).on('submit', '#location_hour_form', function(event) {
            event.preventDefault();
            let form_data = $('#location_hour_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success: function(data) {
                    window.location.href = 'user.php?tab=service&id=' + $('.PK_USER').val();
                }
            });
        });

        $(document).on('submit', '#document_form', function(event) {
            event.preventDefault();
            let form_data = new FormData($('#document_form')[0]);
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                success: function(data) {
                    window.location.href = `user.php?id=${PK_USER}&tab=documents`;
                }
            });
        });

        // Comment Modal
        let comment_model = document.getElementById("commentModel");
        let comment_span = document.getElementsByClassName("close_comment_model")[0];

        function openCommentModel() {
            comment_model.style.display = "block";
        }

        comment_span.onclick = function() {
            comment_model.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == comment_model) {
                comment_model.style.display = "none";
            }
        }

        function createUserComment() {
            $('#comment_header').text("Add Comment");
            $('#PK_COMMENT').val(0);
            $('#COMMENT').val('');
            $('#COMMENT_DATE').val('');
            $('#comment_active').hide();
            openCommentModel();
        }

        function editComment(PK_COMMENT) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                dataType: 'JSON',
                data: {
                    FUNCTION_NAME: 'getEditCommentData',
                    PK_COMMENT: PK_COMMENT
                },
                success: function(data) {
                    $('#comment_header').text("Edit Comment");
                    $('#PK_COMMENT').val(data.fields.PK_COMMENT);
                    $('#COMMENT').val(data.fields.COMMENT);
                    $('#COMMENT_DATE').val(data.fields.COMMENT_DATE);
                    $('#COMMENT_ACTIVE_' + data.fields.ACTIVE).prop('checked', true);
                    $('#comment_active').show();
                    openCommentModel();
                }
            });
        }

        $(document).on('submit', '#comment_add_edit_form', function(event) {
            event.preventDefault();
            let form_data = new FormData($('#comment_add_edit_form')[0]);
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                success: function(data) {
                    window.location.href = `user.php?id=${PK_USER}&tab=comments`;
                }
            });
        });

        function deleteComment(PK_COMMENT) {
            let conf = confirm("Are you sure you want to delete?");
            if (conf) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {
                        FUNCTION_NAME: 'deleteCommentData',
                        PK_COMMENT: PK_COMMENT
                    },
                    success: function(data) {
                        window.location.href = `user.php?id=${PK_USER}&tab=comments`;
                    }
                });
            }
        }

        $(document).ready(function() {
            $('#USER_NAME').on('blur', function() {
                const USER_NAME = $(this).val().trim();
                if (USER_NAME != '') {
                    $.ajax({
                        url: 'ajax/username_checker.php',
                        type: 'post',
                        data: {
                            USER_NAME: USER_NAME
                        },
                        success: function(response) {
                            $('#uname_result').html(response);
                        }
                    });
                } else {
                    $("#uname_result").html("");
                }
            });
        });

        $(document).on('change', '.engagement_terms', function() {
            let row = $(this).closest('.rate-row');
            let inputDiv = row.find('div:last-child');
            if ($(this).is(':checked')) {
                inputDiv.show();
                if ($(this).attr('id') == 4 && $(this).is(':checked')) {
                    $('#5').prop('checked', false).trigger('change');
                }
                if ($(this).attr('id') == 5 && $(this).is(':checked')) {
                    $('#4').prop('checked', false).trigger('change');
                }
            } else {
                inputDiv.hide();
            }
        });

        // Initialize engagement terms visibility
        $(document).ready(function() {
            $('.engagement_terms').each(function() {
                let row = $(this).closest('.rate-row');
                let inputDiv = row.find('div:last-child');
                if ($(this).is(':checked')) {
                    inputDiv.show();
                } else {
                    inputDiv.hide();
                }
            });
        });

        // Service tab - get location hours when tab is clicked
        document.querySelector('[data-tab="service"]')?.addEventListener('click', function() {
            getLocationHours();
        });
    </script>
</body>

</html>