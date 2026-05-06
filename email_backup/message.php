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

// Handle POST requests (Send message)
if (!empty($_POST)) {
    $RECEPTIONS = $_POST['RECEPTION'] ?? [];
    $FILE_NAMES = $_POST['FILE_NAME'] ?? [];
    $FILE_LOCATIONS = $_POST['FILE_LOCATION'] ?? [];
    $PK_EMAIL_ATTACHMENT = $_POST['PK_EMAIL_ATTACHMENT'] ?? [];

    unset($_POST['RECEPTION']);
    unset($_POST['FILE_NAME']);
    unset($_POST['FILE_LOCATION']);
    unset($_POST['PK_EMAIL_ATTACHMENT']);

    if (isset($_POST['REMINDER_DATE']))
        $_POST['REMINDER_DATE'] = date("Y-m-d", strtotime($_POST['REMINDER_DATE']));

    if (isset($_POST['DUE_DATE']))
        $_POST['DUE_DATE'] = date("Y-m-d", strtotime($_POST['DUE_DATE']));

    $EMAIL = $_POST;
    $EMAIL['PK_EMAIL_STATUS'] = 1;
    $EMAIL['CREATED_BY'] = $_SESSION['PK_USER'];
    $EMAIL['CREATED_ON'] = date("Y-m-d H:i");
    $EMAIL['INTERNAL_ID'] = 0;
    $EMAIL['DRAFT'] = isset($_POST['DRAFT']) ? $_POST['DRAFT'] : 0;

    db_perform('DOA_EMAIL', $EMAIL, 'insert');
    $PK_EMAIL = $db->insert_ID();

    $EMAIL1['INTERNAL_ID'] = $PK_EMAIL;
    $INTERNAL_ID = $PK_EMAIL;
    db_perform('DOA_EMAIL', $EMAIL1, 'update', " PK_EMAIL = '$PK_EMAIL' ");

    // Add recipients
    if (!empty($RECEPTIONS)) {
        foreach ($RECEPTIONS as $RECEPTION) {
            $res = $db->Execute("SELECT PK_EMAIL_RECEPTION FROM DOA_EMAIL_RECEPTION WHERE PK_EMAIL = '$PK_EMAIL' AND PK_USER = '$RECEPTION' ");

            if ($res->RecordCount() == 0) {
                $EMAIL_RECEPTION['INTERNAL_ID'] = $INTERNAL_ID;
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

// Get conversation list based on view type
$conversations = [];

if ($view_type == 'inbox') {
    $res = $db->Execute("SELECT DOA_EMAIL.*, DOA_EMAIL_RECEPTION.VIWED, 
                         sender.FIRST_NAME as SENDER_FIRST_NAME, sender.LAST_NAME as SENDER_LAST_NAME,
                         sender.USER_NAME as SENDER_USER_NAME
                         FROM DOA_EMAIL_RECEPTION 
                         INNER JOIN DOA_EMAIL ON DOA_EMAIL.PK_EMAIL = DOA_EMAIL_RECEPTION.PK_EMAIL 
                         LEFT JOIN DOA_USERS sender ON sender.PK_USER = DOA_EMAIL.CREATED_BY
                         WHERE DOA_EMAIL_RECEPTION.PK_USER = $user_id 
                         AND DOA_EMAIL.DRAFT = 0 
                         AND DOA_EMAIL.ACTIVE = 1 
                         AND DOA_EMAIL_RECEPTION.DELETED = 0 
                         GROUP BY DOA_EMAIL.PK_EMAIL
                         ORDER BY DOA_EMAIL.CREATED_ON DESC");
} elseif ($view_type == 'sent') {
    $res = $db->Execute("SELECT DOA_EMAIL.*, 
                         GROUP_CONCAT(DISTINCT CONCAT(recipient.FIRST_NAME, ' ', recipient.LAST_NAME) SEPARATOR ', ') as RECIPIENT_NAMES,
                         GROUP_CONCAT(DISTINCT recipient.USER_NAME SEPARATOR ', ') as RECIPIENT_USERNAMES
                         FROM DOA_EMAIL 
                         LEFT JOIN DOA_EMAIL_RECEPTION ON DOA_EMAIL.PK_EMAIL = DOA_EMAIL_RECEPTION.PK_EMAIL
                         LEFT JOIN DOA_USERS recipient ON recipient.PK_USER = DOA_EMAIL_RECEPTION.PK_USER
                         WHERE DOA_EMAIL.CREATED_BY = $user_id 
                         AND DOA_EMAIL.DRAFT = 0 
                         AND DOA_EMAIL.ACTIVE = 1 
                         GROUP BY DOA_EMAIL.PK_EMAIL
                         ORDER BY DOA_EMAIL.CREATED_ON DESC");
} elseif ($view_type == 'draft') {
    $res = $db->Execute("SELECT DOA_EMAIL.*, 
                         GROUP_CONCAT(DISTINCT CONCAT(recipient.FIRST_NAME, ' ', recipient.LAST_NAME) SEPARATOR ', ') as RECIPIENT_NAMES
                         FROM DOA_EMAIL 
                         LEFT JOIN DOA_EMAIL_RECEPTION ON DOA_EMAIL.PK_EMAIL = DOA_EMAIL_RECEPTION.PK_EMAIL
                         LEFT JOIN DOA_USERS recipient ON recipient.PK_USER = DOA_EMAIL_RECEPTION.PK_USER
                         WHERE DOA_EMAIL.CREATED_BY = $user_id 
                         AND DOA_EMAIL.DRAFT = 1 
                         AND DOA_EMAIL.ACTIVE = 1 
                         GROUP BY DOA_EMAIL.PK_EMAIL
                         ORDER BY DOA_EMAIL.CREATED_ON DESC");
}

if (isset($res) && $res->RecordCount() > 0) {
    while (!$res->EOF) {
        $conversations[] = $res->fields;
        $res->MoveNext();
    }
}

// Get selected conversation messages (without duplicates)
$selected_messages = [];
$selected_conversation_user = null;
$conversation_subject = '';
$conversation_recipients = '';

if (!empty($conversation_id)) {
    // Get the main email
    $res_email = $db->Execute("SELECT * FROM DOA_EMAIL WHERE PK_EMAIL = '$conversation_id' AND ACTIVE = 1");
    if ($res_email->RecordCount() > 0) {
        $conversation_subject = $res_email->fields['SUBJECT'];

        // Get sender info
        $res_sender = $db->Execute("SELECT FIRST_NAME, LAST_NAME, USER_NAME FROM DOA_USERS WHERE PK_USER = '" . $res_email->fields['CREATED_BY'] . "'");
        if ($res_sender->RecordCount() > 0) {
            $selected_conversation_user = $res_sender->fields;
        }

        // For sent items, get recipient info
        if ($view_type == 'sent') {
            $res_recipients = $db->Execute("SELECT GROUP_CONCAT(DISTINCT CONCAT(u.FIRST_NAME, ' ', u.LAST_NAME) SEPARATOR ', ') as RECIPIENT_NAMES
                                           FROM DOA_EMAIL_RECEPTION er
                                           LEFT JOIN DOA_USERS u ON u.PK_USER = er.PK_USER
                                           WHERE er.PK_EMAIL = '$conversation_id'");
            if ($res_recipients->RecordCount() > 0 && $res_recipients->fields['RECIPIENT_NAMES']) {
                $conversation_recipients = $res_recipients->fields['RECIPIENT_NAMES'];
            }
        }

        // Mark as viewed (only for inbox)
        if ($view_type == 'inbox') {
            $db->Execute("UPDATE DOA_EMAIL_RECEPTION SET VIWED = 1 WHERE PK_EMAIL = '$conversation_id' AND PK_USER = '$user_id'");
        }

        // Add main message
        $selected_messages[$res_email->fields['PK_EMAIL']] = $res_email->fields;

        // Get replies (messages where INTERNAL_ID equals this conversation ID)
        $res_replies = $db->Execute("SELECT * FROM DOA_EMAIL WHERE INTERNAL_ID = '$conversation_id' AND ACTIVE = 1 AND PK_EMAIL != '$conversation_id' ORDER BY CREATED_ON ASC");
        while (!$res_replies->EOF) {
            // Use PK_EMAIL as key to prevent duplicates
            $selected_messages[$res_replies->fields['PK_EMAIL']] = $res_replies->fields;
            $res_replies->MoveNext();
        }

        // Sort messages by CREATED_ON
        usort($selected_messages, function ($a, $b) {
            return strtotime($a['CREATED_ON']) - strtotime($b['CREATED_ON']);
        });
    }
}

// Function to get display name for conversation list
function getDisplayName($row, $view_type)
{
    if ($view_type == 'inbox') {
        if (!empty($row['SENDER_FIRST_NAME'])) {
            return trim($row['SENDER_FIRST_NAME'] . ' ' . ($row['SENDER_LAST_NAME'] ?? ''));
        }
        return $row['SENDER_USER_NAME'] ?? 'System User';
    } elseif ($view_type == 'sent') {
        if (!empty($row['RECIPIENT_NAMES'])) {
            return 'To: ' . $row['RECIPIENT_NAMES'];
        }
        return 'Recipient';
    } elseif ($view_type == 'draft') {
        if (!empty($row['RECIPIENT_NAMES'])) {
            return 'To: ' . $row['RECIPIENT_NAMES'];
        }
        return 'Draft';
    }
    return 'User';
}

// Function to get initials
function getInitials($name)
{
    if (empty($name) || $name == 'User') return 'U';
    // Remove "To: " prefix for sent items
    $cleanName = str_replace('To: ', '', $name);
    $words = explode(' ', trim($cleanName));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($cleanName, 0, 2));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messaging Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
            font-weight: bold;
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
        }

        .input-wrapper {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .input-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .recipient-info {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid bg-white rounded border mx-auto">
        <div class="main-wrapper">
            <aside class="sidebar">
                <div class="sidebar-header">
                    <div class="d-flex gap-2 mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" id="searchMessages" placeholder="Search messages...">
                        </div>
                        <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" style="border-radius: 20px;" onclick="window.location.href='message.php?type=<?php echo $view_type; ?>'">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                        <button class="btn btn-sm bg-light-green rounded-circle" data-bs-toggle="modal" data-bs-target="#composeModal">
                            <i class="bi bi-pencil color-white"></i>
                        </button>
                    </div>

                    <ul class="nav nav-tabs nav-fill" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $view_type == 'inbox' ? 'active' : ''; ?>" onclick="window.location.href='message.php?type=inbox'" type="button">Inbox</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $view_type == 'sent' ? 'active' : ''; ?>" onclick="window.location.href='message.php?type=sent'" type="button">Sent</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $view_type == 'draft' ? 'active' : ''; ?>" onclick="window.location.href='message.php?type=draft'" type="button">Drafts</button>
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
                            $displayName = getDisplayName($conv, $view_type);
                            $initials = getInitials($displayName);
                        ?>
                            <div class="conv-item <?php echo (isset($conv['VIWED']) && $conv['VIWED'] == 0 && $view_type == 'inbox') ? 'unread' : ''; ?> 
                             <?php echo ($conversation_id == $conv['PK_EMAIL']) ? 'active' : ''; ?>"
                                onclick="window.location.href='message.php?id=<?php echo $conv['PK_EMAIL']; ?>&type=<?php echo $view_type; ?>'">
                                <div class="d-flex">
                                    <div class="avatar me-3"><?php echo $initials; ?></div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold small"><?php echo htmlspecialchars($displayName); ?></span>
                                            <span class="text-muted small"><?php echo date("m/d/Y", strtotime($conv['CREATED_ON'])); ?></span>
                                        </div>
                                        <p class="text-muted small mb-0 text-truncate">
                                            <strong><?php echo htmlspecialchars(substr($conv['SUBJECT'] ?? '', 0, 40)); ?></strong>
                                        </p>
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
                                    $header_display = $sender_display ?: 'System User';
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
                                        echo htmlspecialchars($sender_display ?: 'System User');
                                    }
                                    ?>
                                </h6>
                                <small class="text-muted"><?php echo htmlspecialchars($conversation_subject); ?></small>
                                <?php if ($view_type == 'sent' && !empty($conversation_recipients)): ?>
                                    <div class="recipient-info">Sent to: <?php echo htmlspecialchars($conversation_recipients); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button class="btn btn-sm bg-light fw-medium" style="border-radius: 20px;" onclick="replyToMessage(<?php echo $conversation_id; ?>)">
                            <i class="bi bi-reply"></i> Reply
                        </button>
                    </header>

                    <div class="chat-body" id="chatBody">
                        <?php foreach ($selected_messages as $msg):
                            $is_sent = ($msg['CREATED_BY'] == $user_id);
                            $sender_info = null;
                            if (!$is_sent) {
                                $res_sender = $db->Execute("SELECT FIRST_NAME, LAST_NAME FROM DOA_USERS WHERE PK_USER = '" . $msg['CREATED_BY'] . "'");
                                if ($res_sender->RecordCount() > 0) {
                                    $sender_info = $res_sender->fields;
                                }
                            }
                            $sender_display_name = $is_sent ? 'You' : trim(($sender_info['FIRST_NAME'] ?? 'User') . ' ' . ($sender_info['LAST_NAME'] ?? ''));
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
                                                <a href="<?php echo $res_attachments->fields['LOCATION']; ?>" target="_blank" class="text-muted small me-2">
                                                    <i class="bi bi-paperclip"></i> <?php echo $res_attachments->fields['FILE_NAME']; ?>
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
                        <form method="post" action="message.php" enctype="multipart/form-data" id="replyForm">
                            <input type="hidden" name="INTERNAL_ID" value="<?php echo $conversation_id; ?>">
                            <input type="hidden" name="DRAFT" id="replyDraft" value="0">
                            <input type="hidden" name="SUBJECT" value="<?php echo htmlspecialchars('Re: ' . $conversation_subject); ?>">
                            <div class="input-wrapper">
                                <textarea name="CONTENT" id="replyContent" rows="3" placeholder="Write a message..." style="border: none; resize: none; outline: none; width: 100%;"></textarea>
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
                                        <button type="submit" class="btn btn-light text-muted btn-sm px-4" style="background: #00B739; color: white; border-radius: 20px;">
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
                        <button class="btn btn-success mt-3" data-bs-toggle="modal" data-bs-target="#composeModal" style="background-color: #00B739;">
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
                <form method="post" action="message.php" enctype="multipart/form-data" id="composeForm">
                    <div class="modal-body">
                        <input type="hidden" name="DRAFT" id="composeDraft" value="0">
                        <div class="mb-3">
                            <label class="form-label">To:</label>
                            <select name="RECEPTION[]" id="recipients" class="form-control select2" multiple required style="width: 100%">
                                <?php foreach ($users_list as $user): ?>
                                    <option value="<?php echo $user['PK_USER']; ?>">
                                        <?php echo htmlspecialchars($user['FIRST_NAME'] . ' ' . ($user['LAST_NAME'] ?? '') . ' (' . $user['USER_NAME'] . ')'); ?>
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
                        <button type="submit" class="btn btn-success" style="background-color: #00B739;">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let composeAttachmentCount = 0;
        let replyAttachmentCount = 0;

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
        });

        function replyToMessage(id) {
            $('#composeModal').modal('show');
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
                        alert(parts[1]);
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
                        alert(parts[1]);
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
                alert('Please enter a subject');
                return;
            }
            if (!$('#recipients').val()) {
                alert('Please select at least one recipient');
                return;
            }
            $('#composeDraft').val('1');
            $('#composeForm').submit();
        }

        function saveReplyAsDraft() {
            if (!$('#replyContent').val()) {
                alert('Please enter a message');
                return;
            }
            $('#replyDraft').val('1');
            $('#replyForm').submit();
        }

        <?php if (!empty($conversation_id)): ?>
            var chatBody = document.getElementById('chatBody');
            if (chatBody) {
                chatBody.scrollTop = chatBody.scrollHeight;
            }
        <?php endif; ?>
    </script>

</body>

</html>