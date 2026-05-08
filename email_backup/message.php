<?php
require_once("../global/config.php");
global $db;
global $db_account;

$title = "Messaging Interface";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '') {
    header("location:../index.php");
    exit;
}

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'] ?? 0;
$user_id = $_SESSION['PK_USER'];

// Check location selection - should be exactly ONE location
$selected_location_id = $_SESSION['DEFAULT_LOCATION_ID'] ?? 0;
$has_valid_location = false;

// If DEFAULT_LOCATION_ID is an array or contains multiple values separated by commas
if (!empty($selected_location_id)) {
    // Check if multiple locations are selected (comma-separated or array)
    if (is_array($selected_location_id)) {
        $location_count = count($selected_location_id);
    } else {
        $location_count = substr_count($selected_location_id, ',') + 1;
    }

    // Valid only if exactly ONE location is selected
    $has_valid_location = ($location_count == 1);
}

// Handle POST requests (Send message)
if (!empty($_POST)) {
    // Validate exactly ONE location is selected before sending (not draft)
    $is_draft = isset($_POST['DRAFT']) ? $_POST['DRAFT'] : 0;

    if ($is_draft == 0 && !$has_valid_location) {
        // Store message data in session to restore after location selection
        $_SESSION['pending_message'] = $_POST;
?>
        <script>
            alert('Please select exactly ONE location from the top dropdown before sending messages.');
            window.location.href = '../dashboard.php';
        </script>
<?php
        exit;
    }

    $RECEPTIONS = $_POST['RECEPTION'] ?? [];
    $FILE_NAMES = $_POST['FILE_NAME'] ?? [];
    $FILE_LOCATIONS = $_POST['FILE_LOCATION'] ?? [];
    $PK_EMAIL_ATTACHMENT = $_POST['PK_EMAIL_ATTACHMENT'] ?? [];
    $PARENT_EMAIL_ID = $_POST['PARENT_EMAIL_ID'] ?? null;

    unset($_POST['RECEPTION']);
    unset($_POST['FILE_NAME']);
    unset($_POST['FILE_LOCATION']);
    unset($_POST['PK_EMAIL_ATTACHMENT']);
    unset($_POST['PARENT_EMAIL_ID']);

    if (isset($_POST['REMINDER_DATE']))
        $_POST['REMINDER_DATE'] = date("Y-m-d", strtotime($_POST['REMINDER_DATE']));

    if (isset($_POST['DUE_DATE']))
        $_POST['DUE_DATE'] = date("Y-m-d", strtotime($_POST['DUE_DATE']));

    $EMAIL = $_POST;
    $EMAIL['PK_EMAIL_STATUS'] = 1;
    $EMAIL['CREATED_BY'] = $_SESSION['PK_USER'];
    $EMAIL['CREATED_ON'] = date("Y-m-d H:i");

    // If this is a reply, set INTERNAL_ID to the parent email ID to group them
    if ($PARENT_EMAIL_ID) {
        // Get the root conversation ID
        $root_check = $db->Execute("SELECT INTERNAL_ID FROM DOA_EMAIL WHERE PK_EMAIL = '$PARENT_EMAIL_ID'");
        if ($root_check->RecordCount() > 0 && $root_check->fields['INTERNAL_ID'] > 0) {
            $EMAIL['INTERNAL_ID'] = $root_check->fields['INTERNAL_ID'];
        } else {
            $EMAIL['INTERNAL_ID'] = $PARENT_EMAIL_ID;
        }
    } else {
        $EMAIL['INTERNAL_ID'] = 0;
    }

    $EMAIL['DRAFT'] = isset($_POST['DRAFT']) ? $_POST['DRAFT'] : 0;

    db_perform('DOA_EMAIL', $EMAIL, 'insert');
    $PK_EMAIL = $db->insert_ID();

    // If this is a new conversation (not a reply), set INTERNAL_ID to itself
    if (!$PARENT_EMAIL_ID && $EMAIL['DRAFT'] == 0) {
        $db->Execute("UPDATE DOA_EMAIL SET INTERNAL_ID = $PK_EMAIL WHERE PK_EMAIL = $PK_EMAIL");
    }

    // Add recipients
    if (!empty($RECEPTIONS)) {
        foreach ($RECEPTIONS as $RECEPTION) {
            $res = $db->Execute("SELECT PK_EMAIL_RECEPTION FROM DOA_EMAIL_RECEPTION WHERE PK_EMAIL = '$PK_EMAIL' AND PK_USER = '$RECEPTION' ");

            if ($res->RecordCount() == 0) {
                $EMAIL_RECEPTION['INTERNAL_ID'] = ($PARENT_EMAIL_ID) ? ($EMAIL['INTERNAL_ID'] ?: $PK_EMAIL) : $PK_EMAIL;
                $EMAIL_RECEPTION['PK_EMAIL'] = $PK_EMAIL;
                $EMAIL_RECEPTION['PK_USER'] = $RECEPTION;
                $EMAIL_RECEPTION['VIWED'] = 0;
                $EMAIL_RECEPTION['REPLY'] = 0;
                $EMAIL_RECEPTION['DELETED'] = 0;
                $EMAIL_RECEPTION['CREATED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_EMAIL_RECEPTION', $EMAIL_RECEPTION, 'insert');
            }
        }
    }

    // Handle attachments
    if (!empty($FILE_NAMES)) {
        $i = 0;
        foreach ($FILE_NAMES as $FILE_NAME) {
            if (!empty($FILE_NAME)) {
                $EMAIL_ATTACHMENT['PK_EMAIL'] = $PK_EMAIL;
                $EMAIL_ATTACHMENT['FILE_NAME'] = $FILE_NAME;
                $EMAIL_ATTACHMENT['LOCATION'] = $FILE_LOCATIONS[$i];
                $EMAIL_ATTACHMENT['UPLOADED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_EMAIL_ATTACHMENT', $EMAIL_ATTACHMENT, 'insert');
            }
            $i++;
        }
    }

    if ($_POST['DRAFT'] == 0) {
        header("location:message.php?type=sent");
    } else {
        header("location:message.php?type=draft");
    }
    exit;
}

// Get view type
$view_type = empty($_GET['type']) ? 'inbox' : $_GET['type'];
$conversation_id = empty($_GET['id']) ? '' : $_GET['id'];

// Get users for recipient selection
$users_list = [];
if ($_SESSION['PK_ROLES'] == 4) {
    $res_users = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_NAME 
                               FROM DOA_USERS 
                               LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER 
                               LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER 
                               WHERE DOA_USERS.IS_RECIPIENT = 1 
                               AND DOA_USERS.ACTIVE = '1' 
                               AND (DOA_USERS.IS_DELETED = 0 || DOA_USERS.IS_DELETED IS NULL) 
                               AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
                               AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " 
                               ORDER BY DOA_USERS.FIRST_NAME ASC");
} else {
    $res_users = $db->Execute("SELECT PK_USER, USER_NAME, FIRST_NAME, LAST_NAME 
                               FROM DOA_USERS 
                               WHERE ACTIVE = '1' 
                               AND PK_ACCOUNT_MASTER = $PK_ACCOUNT_MASTER 
                               AND PK_USER != $user_id 
                               ORDER BY FIRST_NAME ASC");
}

while (!$res_users->EOF) {
    $users_list[] = $res_users->fields;
    $res_users->MoveNext();
}

// Get conversation list - Group by INTERNAL_ID to show threads
$conversations = [];

if ($view_type == 'inbox') {
    // Get all conversations where user is recipient
    $res = $db->Execute("SELECT 
                        COALESCE(e.INTERNAL_ID, e.PK_EMAIL) as THREAD_ID,
                        MAX(e.PK_EMAIL) as LATEST_EMAIL_ID,
                        MAX(e.CREATED_ON) as LAST_MESSAGE_DATE,
                        MIN(e.CREATED_ON) as FIRST_MESSAGE_DATE,
                        e.SUBJECT,
                        MAX(CASE WHEN er.VIWED = 0 THEN 1 ELSE 0 END) as HAS_UNREAD,
                        sender.FIRST_NAME as SENDER_FIRST_NAME, 
                        sender.LAST_NAME as SENDER_LAST_NAME,
                        sender.USER_NAME as SENDER_USER_NAME,
                        (SELECT CONTENT FROM DOA_EMAIL e2 
                         WHERE (e2.INTERNAL_ID = THREAD_ID OR e2.PK_EMAIL = THREAD_ID)
                         AND e2.ACTIVE = 1 
                         ORDER BY e2.CREATED_ON DESC LIMIT 1) as LAST_MESSAGE,
                        (SELECT CREATED_BY FROM DOA_EMAIL e2 
                         WHERE (e2.INTERNAL_ID = THREAD_ID OR e2.PK_EMAIL = THREAD_ID)
                         AND e2.ACTIVE = 1 
                         ORDER BY e2.CREATED_ON DESC LIMIT 1) as LAST_SENDER
                        FROM DOA_EMAIL_RECEPTION er 
                        INNER JOIN DOA_EMAIL e ON e.PK_EMAIL = er.PK_EMAIL 
                        LEFT JOIN DOA_USERS sender ON sender.PK_USER = e.CREATED_BY
                        WHERE er.PK_USER = $user_id 
                        AND e.DRAFT = 0 
                        AND e.ACTIVE = 1 
                        AND er.DELETED = 0 
                        GROUP BY THREAD_ID
                        ORDER BY LAST_MESSAGE_DATE DESC");
} elseif ($view_type == 'sent') {
    // Get all conversations where user is sender
    $res = $db->Execute("SELECT 
                        COALESCE(e.INTERNAL_ID, e.PK_EMAIL) as THREAD_ID,
                        MAX(e.PK_EMAIL) as LATEST_EMAIL_ID,
                        MAX(e.CREATED_ON) as LAST_MESSAGE_DATE,
                        e.SUBJECT,
                        GROUP_CONCAT(DISTINCT CONCAT(u.FIRST_NAME, ' ', u.LAST_NAME) SEPARATOR ', ') as RECIPIENT_NAMES,
                        (SELECT CONTENT FROM DOA_EMAIL e2 
                         WHERE (e2.INTERNAL_ID = THREAD_ID OR e2.PK_EMAIL = THREAD_ID)
                         AND e2.ACTIVE = 1 
                         ORDER BY e2.CREATED_ON DESC LIMIT 1) as LAST_MESSAGE
                        FROM DOA_EMAIL e
                        LEFT JOIN DOA_EMAIL_RECEPTION er ON e.PK_EMAIL = er.PK_EMAIL
                        LEFT JOIN DOA_USERS u ON u.PK_USER = er.PK_USER
                        WHERE e.CREATED_BY = $user_id 
                        AND e.DRAFT = 0 
                        AND e.ACTIVE = 1 
                        GROUP BY THREAD_ID
                        ORDER BY LAST_MESSAGE_DATE DESC");
} elseif ($view_type == 'draft') {
    $res = $db->Execute("SELECT 
                        e.PK_EMAIL,
                        e.SUBJECT,
                        e.CONTENT,
                        e.CREATED_ON,
                        GROUP_CONCAT(DISTINCT CONCAT(u.FIRST_NAME, ' ', u.LAST_NAME) SEPARATOR ', ') as RECIPIENT_NAMES
                        FROM DOA_EMAIL e
                        LEFT JOIN DOA_EMAIL_RECEPTION er ON e.PK_EMAIL = er.PK_EMAIL
                        LEFT JOIN DOA_USERS u ON u.PK_USER = er.PK_USER
                        WHERE e.CREATED_BY = $user_id 
                        AND e.DRAFT = 1 
                        AND e.ACTIVE = 1 
                        GROUP BY e.PK_EMAIL
                        ORDER BY e.CREATED_ON DESC");
}

if (isset($res) && $res->RecordCount() > 0) {
    while (!$res->EOF) {
        $conversations[] = $res->fields;
        $res->MoveNext();
    }
}

// Get selected conversation messages (all messages in this thread)
$selected_messages = [];
$selected_conversation_user = null;
$conversation_subject = '';
$conversation_recipients = '';
$other_participant_id = null;
$thread_id = $conversation_id;

if (!empty($conversation_id)) {
    // Determine the thread ID (could be either INTERNAL_ID or PK_EMAIL)
    $check_thread = $db->Execute("SELECT COALESCE(INTERNAL_ID, PK_EMAIL) as THREAD_ID, SUBJECT, CREATED_BY 
                                  FROM DOA_EMAIL 
                                  WHERE PK_EMAIL = '$conversation_id' AND ACTIVE = 1");

    if ($check_thread->RecordCount() > 0) {
        $thread_id = $check_thread->fields['THREAD_ID'];
        $conversation_subject = $check_thread->fields['SUBJECT'];

        // Get all messages in this thread (where INTERNAL_ID = thread_id OR PK_EMAIL = thread_id for root)
        $res_messages = $db->Execute("SELECT e.*, 
                                      (SELECT FIRST_NAME FROM DOA_USERS WHERE PK_USER = e.CREATED_BY) as SENDER_FNAME,
                                      (SELECT LAST_NAME FROM DOA_USERS WHERE PK_USER = e.CREATED_BY) as SENDER_LNAME
                                      FROM DOA_EMAIL e 
                                      WHERE (e.INTERNAL_ID = '$thread_id' OR e.PK_EMAIL = '$thread_id')
                                      AND e.ACTIVE = 1 
                                      AND e.DRAFT = 0
                                      ORDER BY e.CREATED_ON ASC");

        if ($res_messages->RecordCount() > 0) {
            // Get all unique participants in this conversation
            $participants = [];
            // $res_messages->MoveFirst();
            while (!$res_messages->EOF) {
                $participants[] = $res_messages->fields['CREATED_BY'];

                // Also get recipients
                $res_recep = $db->Execute("SELECT PK_USER FROM DOA_EMAIL_RECEPTION WHERE PK_EMAIL = '" . $res_messages->fields['PK_EMAIL'] . "'");
                while (!$res_recep->EOF) {
                    $participants[] = $res_recep->fields['PK_USER'];
                    $res_recep->MoveNext();
                }

                $res_messages->MoveNext();
            }

            // Find the other participant (not the current user)
            $participants = array_unique($participants);
            foreach ($participants as $participant) {
                if ($participant != $user_id && $participant > 0) {
                    $other_participant_id = $participant;
                    $res_other = $db->Execute("SELECT FIRST_NAME, LAST_NAME, USER_NAME FROM DOA_USERS WHERE PK_USER = '$participant'");
                    if ($res_other->RecordCount() > 0) {
                        $selected_conversation_user = $res_other->fields;
                    }
                    break;
                }
            }

            // Mark messages as viewed (for inbox)
            if ($view_type == 'inbox') {
                $db->Execute("UPDATE DOA_EMAIL_RECEPTION er 
                             INNER JOIN DOA_EMAIL e ON e.PK_EMAIL = er.PK_EMAIL 
                             SET er.VIWED = 1 
                             WHERE (e.INTERNAL_ID = '$thread_id' OR e.PK_EMAIL = '$thread_id')
                             AND er.PK_USER = '$user_id'");
            }

            // Get all messages again
            $res_messages = $db->Execute("SELECT e.*, 
                                          (SELECT FIRST_NAME FROM DOA_USERS WHERE PK_USER = e.CREATED_BY) as SENDER_FNAME,
                                          (SELECT LAST_NAME FROM DOA_USERS WHERE PK_USER = e.CREATED_BY) as SENDER_LNAME
                                          FROM DOA_EMAIL e 
                                          WHERE (e.INTERNAL_ID = '$thread_id' OR e.PK_EMAIL = '$thread_id')
                                          AND e.ACTIVE = 1 
                                          AND e.DRAFT = 0
                                          ORDER BY e.CREATED_ON ASC");

            while (!$res_messages->EOF) {
                $selected_messages[] = $res_messages->fields;
                $res_messages->MoveNext();
            }
        }
    }
}

// Function to get display name for conversation list
function getDisplayName($conv, $view_type, $user_id)
{
    global $db;

    if ($view_type == 'inbox') {
        // Get the last sender (most recent message)
        $last_sender = $conv['LAST_SENDER'] ?? $conv['SENDER_FIRST_NAME'];

        if ($last_sender && $last_sender != $user_id) {
            $res = $db->Execute("SELECT FIRST_NAME, LAST_NAME FROM DOA_USERS WHERE PK_USER = '$last_sender'");
            if ($res->RecordCount() > 0) {
                return trim($res->fields['FIRST_NAME'] . ' ' . ($res->fields['LAST_NAME'] ?? ''));
            }
        }

        if (!empty($conv['SENDER_FIRST_NAME'])) {
            return trim($conv['SENDER_FIRST_NAME'] . ' ' . ($conv['SENDER_LAST_NAME'] ?? ''));
        }
        return 'User';
    } elseif ($view_type == 'sent') {
        return ($conv['RECIPIENT_NAMES'] ?? 'Recipient');
    } elseif ($view_type == 'draft') {
        return 'Draft: ' . ($conv['RECIPIENT_NAMES'] ?? 'No recipients');
    }
    return 'User';
}

// Function to get initials
function getInitials($name)
{
    if (empty($name) || $name == 'User') return 'U';
    $cleanName = str_replace('To: ', '', $name);
    $cleanName = str_replace('Draft: ', '', $cleanName);
    $words = explode(' ', trim($cleanName));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($cleanName, 0, 2));
}

// Function to get profile badge
// function getProfileBadge($CUSTOMER_NAME)
// {
//     $customer_initial = getInitials($CUSTOMER_NAME);
//     $colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#ef4444', '#f97316', '#f59e0b', '#84cc16', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9', '#3b82f6'];
//     $color_index = abs(crc32($CUSTOMER_NAME)) % count($colors);
//     $color = $colors[$color_index];
//     return ['initials' => $customer_initial, 'color' => $color];
// }

// Determine location status message
$location_status_message = "";
$location_status_class = "";
if (!$selected_location_id) {
    $location_status_message = "No location selected!";
    $location_status_class = "danger";
} else {
    $location_count = is_array($selected_location_id) ? count($selected_location_id) : substr_count($selected_location_id, ',') + 1;
    if ($location_count > 1) {
        $location_status_message = "Multiple locations selected ($location_count locations)! Please select exactly ONE location.";
        $location_status_class = "danger";
    } else {
        $location_status_message = "✓ Location selected: " . ($_SESSION['DEFAULT_LOCATION_NAME'] ?? 'Selected');
        $location_status_class = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messaging Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #fff;
        }

        .main-wrapper {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 360px;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            background-color: #fff;
        }

        .sidebar-header {
            padding: 15px;
        }

        .nav-tabs {
            border-bottom: 1px solid #eee;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #666;
            font-weight: 500;
            padding: 10px 0;
        }

        .nav-tabs .nav-link.active {
            color: #000;
            border-bottom: 2px solid #00B739;
        }

        .conversation-list {
            flex-grow: 1;
            overflow-y: auto;
        }

        .conv-item {
            padding: 20px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }

        .conv-item:hover {
            background-color: #f8f9fa;
        }

        .conv-item.unread {
            background-color: #f0f7ff;
        }

        .conv-item.active {
            background-color: #e8f5e9;
            border-left: 3px solid #00B739;
        }

        .chat-container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background-color: #fff;
        }

        .chat-header {
            padding: 15px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-body {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }

        .message-row {
            display: flex;
            margin-bottom: 24px;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-content {
            margin-left: 15px;
            max-width: 500px;
        }

        .message-row.sent {
            justify-content: flex-end;
        }

        .message-row.sent .message-content {
            margin-right: 15px;
            margin-left: 0;
        }

        .message-row.sent .bubble {
            background-color: #00B739;
            color: white;
            padding: 12px 16px;
            border-radius: 12px;
        }

        .message-row.received .bubble {
            background-color: #f8f9fa;
            padding: 12px 16px;
            border-radius: 12px;
        }

        .input-area {
            padding: 20px;
            background-color: #fff;
            border-top: 1px solid #eee;
        }

        .input-wrapper {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
        }

        .input-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .bg-light-green {
            background-color: #00B739;
        }

        .color-white {
            color: #fff;
        }

        .modal-header.bg-green {
            background-color: #00B739;
            color: white;
        }

        .attachment-item {
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
            margin-top: 5px;
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 50px;
            color: #999;
        }

        .avatarname {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-weight: 600;
            font-size: 14px;
            margin-right: 10px;
        }

        .last-message-preview {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .message-textarea {
            border: none;
            resize: none;
            outline: none;
            width: 100%;
        }

        .message-textarea:focus {
            outline: none;
        }

        /* Location status banner */
        .location-status {
            padding: 12px 20px;
            margin: 0;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 0;
        }

        .location-status-danger {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        .location-status-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }

        .location-status-success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
    </style>
</head>

<body>

    <div class="container-fluid bg-white rounded border mx-auto">
        <!-- Location Status Banner -->
        <?php if (!$has_valid_location): ?>
            <div class="location-status location-status-<?php echo $location_status_class; ?>" id="locationStatusBanner">
                <div>
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Location Issue!</strong> <?php echo $location_status_message; ?>
                </div>
                <button type="button" class="btn-close" onclick="$('#locationStatusBanner').fadeOut()"></button>
            </div>
        <?php elseif ($has_valid_location): ?>
            <div class="location-status location-status-success" id="locationStatusBanner">
                <div>
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo $location_status_message; ?>
                </div>
                <button type="button" class="btn-close" onclick="$('#locationStatusBanner').fadeOut()"></button>
            </div>
        <?php endif; ?>

        <div class="main-wrapper">
            <aside class="sidebar">
                <div class="sidebar-header">
                    <div class="d-flex gap-2 mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" id="searchMessages" placeholder="Search messages...">
                        </div>
                        <button class="btn btn-sm bg-light-green rounded-circle" id="composeBtn" data-bs-toggle="modal" data-bs-target="#composeModal">
                            <i class="bi bi-pencil color-white"></i>
                        </button>
                    </div>

                    <ul class="nav nav-tabs nav-fill">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $view_type == 'inbox' ? 'active' : ''; ?>" href="message.php?type=inbox">Inbox</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $view_type == 'sent' ? 'active' : ''; ?>" href="message.php?type=sent">Sent</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $view_type == 'draft' ? 'active' : ''; ?>" href="message.php?type=draft">Drafts</a>
                        </li>
                    </ul>
                </div>

                <div class="conversation-list" id="conversationList">
                    <?php if (empty($conversations)): ?>
                        <div class="empty-state">
                            <i class="bi bi-envelope-open" style="font-size: 48px;"></i>
                            <p class="mt-3">No messages found</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv):
                            $displayName = getDisplayName($conv, $view_type, $user_id);
                            $initials = getInitials($displayName);
                            $customer = getProfileBadge($displayName);
                            $customer_initial = $customer['initials'];
                            $customer_color = $customer['color'];
                            $has_unread = isset($conv['HAS_UNREAD']) && $conv['HAS_UNREAD'] == 1;
                            $last_message = isset($conv['LAST_MESSAGE']) ? substr($conv['LAST_MESSAGE'], 0, 60) : '';
                            $conv_id = isset($conv['THREAD_ID']) ? $conv['THREAD_ID'] : $conv['PK_EMAIL'];
                        ?>
                            <div class="conv-item <?php echo $has_unread ? 'unread' : ''; ?> 
                             <?php echo ($conversation_id == $conv_id) ? 'active' : ''; ?>"
                                onclick="window.location.href='message.php?id=<?php echo urlencode($conv_id); ?>&type=<?php echo $view_type; ?>'">
                                <div class="d-flex">
                                    <div><span class="avatarname" style="color: #fff; background-color: <?= $customer_color ?>;"><?= $customer_initial; ?></span></div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold small"><?php echo htmlspecialchars($displayName); ?></span>
                                            <span class="text-muted small"><?php echo date("m/d/Y", strtotime($conv['LAST_MESSAGE_DATE'] ?? $conv['CREATED_ON'])); ?></span>
                                        </div>
                                        <p class="text-muted small mb-0 text-truncate">
                                            <strong><?php echo htmlspecialchars(substr($conv['SUBJECT'] ?? '', 0, 40)); ?></strong>
                                        </p>
                                        <?php if ($last_message): ?>
                                            <div class="last-message-preview">
                                                <?php echo htmlspecialchars($last_message); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </aside>

            <main class="chat-container">
                <?php if (!empty($conversation_id) && !empty($selected_messages)): ?>
                    <header class="chat-header">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3">
                                <?php
                                if ($view_type == 'sent') {
                                    $header_display = 'To: ' . ($conversation_recipients ?: 'Recipient');
                                } else {
                                    $sender_display = trim(($selected_conversation_user['FIRST_NAME'] ?? '') . ' ' . ($selected_conversation_user['LAST_NAME'] ?? ''));
                                    $header_display = $sender_display ?: 'User';
                                }
                                echo getInitials($header_display);
                                ?>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">
                                    <?php
                                    if ($view_type == 'sent') {
                                        echo 'To: ' . htmlspecialchars($conversation_recipients ?: 'Recipient');
                                    } else {
                                        echo htmlspecialchars($sender_display ?: 'User');
                                    }
                                    ?>
                                </h6>
                                <small class="text-muted"><?php echo htmlspecialchars($conversation_subject); ?></small>
                            </div>
                        </div>
                        <button class="btn btn-sm bg-light fw-medium reply-btn" style="border-radius: 20px;" onclick="replyToMessage('<?php echo htmlspecialchars($conversation_id); ?>', '<?php echo htmlspecialchars(addslashes($conversation_subject)); ?>', '<?php echo htmlspecialchars($other_participant_id); ?>')">
                            <i class="bi bi-reply"></i> Reply
                        </button>
                    </header>

                    <div class="chat-body" id="chatBody">
                        <?php foreach ($selected_messages as $msg):
                            $is_sent = ($msg['CREATED_BY'] == $user_id);
                            $sender_display_name = $is_sent ? 'You' : trim(($msg['SENDER_FNAME'] ?? 'User') . ' ' . ($msg['SENDER_LNAME'] ?? ''));
                        ?>
                            <div class="message-row <?php echo $is_sent ? 'sent' : 'received'; ?>">
                                <?php if (!$is_sent): ?>
                                    <div class="avatar"><?php echo getInitials($sender_display_name); ?></div>
                                <?php endif; ?>
                                <div class="message-content">
                                    <div class="mb-1">
                                        <span class="fw-bold small"><?php echo htmlspecialchars($sender_display_name); ?></span>
                                        <span class="text-muted small ms-2"><?php echo date("g:i A, M d", strtotime($msg['CREATED_ON'])); ?></span>
                                    </div>
                                    <div class="bubble">
                                        <?php echo nl2br(htmlspecialchars($msg['CONTENT'] ?? '')); ?>
                                    </div>
                                    <?php
                                    $res_attachments = $db->Execute("SELECT * FROM DOA_EMAIL_ATTACHMENT WHERE PK_EMAIL = '" . $msg['PK_EMAIL'] . "'");
                                    if ($res_attachments->RecordCount() > 0):
                                    ?>
                                        <div class="mt-2">
                                            <?php while (!$res_attachments->EOF): ?>
                                                <a href="<?php echo htmlspecialchars($res_attachments->fields['LOCATION']); ?>" target="_blank" class="text-muted small me-2">
                                                    <i class="bi bi-paperclip"></i> <?php echo htmlspecialchars($res_attachments->fields['FILE_NAME']); ?>
                                                </a>
                                            <?php
                                                $res_attachments->MoveNext();
                                            endwhile; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($is_sent): ?>
                                    <div class="avatar ms-3" style="background-color: #e8f5e9;">
                                        <?php echo getInitials($_SESSION['FIRST_NAME'] ?? $_SESSION['USER_NAME'] ?? 'You'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <footer class="input-area">
                        <form method="post" action="message.php" enctype="multipart/form-data" id="replyForm" onsubmit="return validateLocationBeforeSend(event, 'reply');">
                            <input type="hidden" name="PARENT_EMAIL_ID" id="parentEmailId" value="<?php echo htmlspecialchars($conversation_id); ?>">
                            <input type="hidden" name="RECEPTION[]" id="replyRecipient" value="<?php echo htmlspecialchars($other_participant_id); ?>">
                            <input type="hidden" name="DRAFT" id="replyDraft" value="0">
                            <input type="hidden" name="SUBJECT" id="replySubject" value="">
                            <div class="input-wrapper">
                                <textarea name="CONTENT" id="replyContent" class="message-textarea" rows="3" placeholder="Write a message..."></textarea>
                                <div id="replyAttachments"></div>
                                <div class="input-actions">
                                    <div class="d-flex gap-3 text-secondary">
                                        <label style="cursor:pointer">
                                            <i class="bi bi-paperclip"></i>
                                            <input type="file" name="FILE[]" style="display:none" onchange="uploadReplyAttachment(this)">
                                        </label>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-light text-muted btn-sm px-4" style="background: #F5F7FA; border-radius: 20px;" onclick="saveReplyAsDraft()">Save Draft</button>
                                        <button type="submit" class="btn btn-light text-muted btn-sm px-4 send-reply-btn" style="background: #00B739; color: white; border-radius: 20px;">
                                            Send <i class="bi bi-send-fill ms-1"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </footer>
                <?php else: ?>
                    <div class="empty-state" style="margin-top: 20%;">
                        <i class="bi bi-chat-dots" style="font-size: 64px; color: #ddd;"></i>
                        <h5 class="mt-3">No conversation selected</h5>
                        <p class="text-muted">Select a message from the sidebar or compose a new one</p>
                        <button class="btn btn-success mt-3 compose-new-btn" data-bs-toggle="modal" data-bs-target="#composeModal" style="background-color: #00B739;">
                            <i class="bi bi-envelope-plus"></i> Compose New Message
                        </button>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Compose Modal -->
    <div class="modal fade" id="composeModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-green">
                    <h5 class="modal-title"><i class="bi bi-envelope-plus"></i> Compose New Message</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="message.php" enctype="multipart/form-data" id="composeForm" onsubmit="return validateLocationBeforeSend(event, 'compose');">
                    <div class="modal-body">
                        <input type="hidden" name="DRAFT" id="composeDraft" value="0">
                        <div class="mb-3">
                            <label class="form-label">To:</label>
                            <select name="RECEPTION[]" id="recipients" class="form-control select2" multiple required style="width: 100%">
                                <?php foreach ($users_list as $user): ?>
                                    <option value="<?php echo $user['PK_USER']; ?>">
                                        <?php echo htmlspecialchars($user['FIRST_NAME'] . ' ' . ($user['LAST_NAME'] ?? '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject:</label>
                            <input type="text" name="SUBJECT" id="composeSubject" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message:</label>
                            <textarea name="CONTENT" id="composeContent" rows="8" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Attachments:</label>
                            <input type="file" name="FILE[]" id="composeFile" class="form-control" onchange="uploadComposeAttachment(this)">
                            <div id="composeAttachments" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-light" onclick="saveComposeAsDraft()">Save Draft</button>
                        <button type="submit" class="btn btn-success send-message-btn" style="background-color: #00B739;">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let composeAttachmentCount = 0;
        let replyAttachmentCount = 0;

        // Location validation status from PHP - must have exactly ONE location
        var hasValidLocation = <?php echo $has_valid_location ? 'true' : 'false'; ?>;

        // Validate exactly ONE location is selected before sending message
        function validateLocationBeforeSend(event, formType) {
            // Check if this is a draft save (drafts should be allowed even without location)
            var isDraft = false;
            if (formType === 'compose') {
                isDraft = document.getElementById('composeDraft').value === '1';
            } else if (formType === 'reply') {
                isDraft = document.getElementById('replyDraft').value === '1';
            }

            // Allow draft saving without location validation
            if (isDraft) {
                return true;
            }

            // Check if exactly ONE location is selected
            if (!hasValidLocation) {
                event.preventDefault();
                event.stopPropagation();

                Swal.fire({
                    icon: 'warning',
                    title: 'Location Selection Required',
                    html: '<strong>Please select exactly ONE location</strong><br><br>You currently have:<br>' +
                        '<?php echo $selected_location_id ? (is_array($selected_location_id) ? count($selected_location_id) . " locations selected" : (substr_count($selected_location_id, ',') + 1) . " locations selected") : "No location selected"; ?>' +
                        '<br><br>Please go back and select only one location from the top dropdown.',
                    confirmButtonColor: '#00B739',
                    confirmButtonText: 'OK',
                    backdrop: true,
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Close modal if open
                        var modal = bootstrap.Modal.getInstance(document.getElementById('composeModal'));
                        if (modal) {
                            modal.hide();
                        }
                    }
                });
                return false;
            }
            return true;
        }

        $(document).ready(function() {
            $('.select2').select2({
                dropdownParent: $('#composeModal'),
                placeholder: 'Select recipients',
                allowClear: true
            });

            $('#searchMessages').on('keyup', function() {
                let searchTerm = $(this).val().toLowerCase();
                $('.conv-item').each(function() {
                    let text = $(this).text().toLowerCase();
                    if (text.indexOf(searchTerm) === -1) {
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                });
            });

            // Auto-scroll to bottom of chat
            var chatBody = document.getElementById('chatBody');
            if (chatBody) {
                chatBody.scrollTop = chatBody.scrollHeight;
            }

            // Show location warning when trying to compose without exactly ONE location
            $('#composeBtn, .compose-new-btn').on('click', function(e) {
                if (!hasValidLocation) {
                    e.preventDefault();
                    e.stopPropagation();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Location Selection Required',
                        html: '<strong>Please select exactly ONE location</strong><br><br>You currently have:<br>' +
                            '<?php echo $selected_location_id ? (is_array($selected_location_id) ? count($selected_location_id) . " locations selected" : (substr_count($selected_location_id, ',') + 1) . " locations selected") : "No location selected"; ?>' +
                            '<br><br>Please select only one location from the top dropdown to compose messages.',
                        confirmButtonColor: '#00B739',
                        confirmButtonText: 'OK'
                    });
                    return false;
                }
            });
        });

        function replyToMessage(conversationId, subject, recipientId) {
            if (!hasValidLocation) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Location Selection Required',
                    html: '<strong>Please select exactly ONE location</strong><br><br>You currently have:<br>' +
                        '<?php echo $selected_location_id ? (is_array($selected_location_id) ? count($selected_location_id) . " locations selected" : (substr_count($selected_location_id, ',') + 1) . " locations selected") : "No location selected"; ?>' +
                        '<br><br>Please select only one location from the top dropdown to reply to messages.',
                    confirmButtonColor: '#00B739',
                    confirmButtonText: 'OK'
                });
                return false;
            }
            $('#parentEmailId').val(conversationId);
            $('#replyRecipient').val(recipientId);
            $('#replySubject').val('Re: ' + subject);
            $('#replyContent').focus();
        }

        function uploadComposeAttachment(input) {
            let file = input.files[0];
            if (!file) return;

            let formData = new FormData();
            formData.append('file', file);

            $.ajax({
                url: 'ajax_upload.php',
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(data) {
                    let parts = data.split('||');
                    if (parts[0] == 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: parts[1],
                            confirmButtonColor: '#00B739'
                        });
                    } else {
                        let attachmentHtml = '<div id="compose_attach_' + composeAttachmentCount + '" class="attachment-item">';
                        attachmentHtml += '<input type="hidden" name="FILE_NAME[]" value="' + parts[1] + '">';
                        attachmentHtml += '<input type="hidden" name="FILE_LOCATION[]" value="' + parts[2] + '">';
                        attachmentHtml += '<i class="bi bi-file-earmark"></i> ' + parts[1];
                        attachmentHtml += ' <a href="javascript:void(0)" onclick="removeComposeAttachment(' + composeAttachmentCount + ')" class="text-danger float-end"><i class="bi bi-x-circle"></i></a>';
                        attachmentHtml += '</div>';
                        $('#composeAttachments').append(attachmentHtml);
                        composeAttachmentCount++;
                        $('#composeFile').val('');
                    }
                }
            });
        }

        function removeComposeAttachment(index) {
            $('#compose_attach_' + index).remove();
        }

        function uploadReplyAttachment(input) {
            let file = input.files[0];
            if (!file) return;

            let formData = new FormData();
            formData.append('file', file);

            $.ajax({
                url: 'ajax_upload.php',
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(data) {
                    let parts = data.split('||');
                    if (parts[0] == 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: parts[1],
                            confirmButtonColor: '#00B739'
                        });
                    } else {
                        let attachmentHtml = '<div id="reply_attach_' + replyAttachmentCount + '" class="attachment-item">';
                        attachmentHtml += '<input type="hidden" name="FILE_NAME[]" value="' + parts[1] + '">';
                        attachmentHtml += '<input type="hidden" name="FILE_LOCATION[]" value="' + parts[2] + '">';
                        attachmentHtml += '<small><i class="bi bi-paperclip"></i> ' + parts[1] + '</small>';
                        attachmentHtml += ' <a href="javascript:void(0)" onclick="removeReplyAttachment(' + replyAttachmentCount + ')" class="text-danger float-end"><i class="bi bi-x-sm"></i></a>';
                        attachmentHtml += '</div>';
                        $('#replyAttachments').append(attachmentHtml);
                        replyAttachmentCount++;
                        input.value = '';
                    }
                }
            });
        }

        function removeReplyAttachment(index) {
            $('#reply_attach_' + index).remove();
        }

        function saveComposeAsDraft() {
            if (!$('#composeSubject').val()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Subject',
                    text: 'Please enter a subject',
                    confirmButtonColor: '#00B739'
                });
                return;
            }
            if (!$('#recipients').val()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Recipient',
                    text: 'Please select at least one recipient',
                    confirmButtonColor: '#00B739'
                });
                return;
            }
            $('#composeDraft').val('1');
            $('#composeForm').submit();
        }

        function saveReplyAsDraft() {
            if (!$('#replyContent').val()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Message',
                    text: 'Please enter a message',
                    confirmButtonColor: '#00B739'
                });
                return;
            }
            $('#replyDraft').val('1');
            $('#replyForm').submit();
        }

        // Refresh page periodically to check for new messages (optional)
        setTimeout(function() {
            if (window.location.href.indexOf('id=') > -1) {
                location.reload();
            }
        }, 30000);
    </script>

</body>

</html>