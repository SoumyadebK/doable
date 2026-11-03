<?php
require_once("../global/config.php");
session_start();

header('Content-Type: application/json');

// Check if draft_id is provided
if (!isset($_POST['draft_id']) || empty($_POST['draft_id'])) {
    echo json_encode(['success' => false, 'message' => 'No draft ID provided']);
    exit;
}

$draft_id = intval($_POST['draft_id']);
$user_id = $_SESSION['PK_USER'];

// Check if user is logged in
if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get draft details
$draft = $db->Execute("SELECT PK_EMAIL, SUBJECT, CONTENT FROM DOA_EMAIL WHERE PK_EMAIL = '$draft_id' AND CREATED_BY = '$user_id' AND DRAFT = 1 AND ACTIVE = 1");

if (!$draft || $draft->RecordCount() == 0) {
    echo json_encode(['success' => false, 'message' => 'Draft not found or you don\'t have permission to edit it']);
    exit;
}

// Get recipients (just the IDs)
$recipient_ids = [];
$res_recipients = $db->Execute("SELECT PK_USER FROM DOA_EMAIL_RECEPTION WHERE PK_EMAIL = '$draft_id'");
if ($res_recipients) {
    while (!$res_recipients->EOF) {
        $recipient_ids[] = (int)$res_recipients->fields['PK_USER'];
        $res_recipients->MoveNext();
    }
}

// Get attachments
$attachments = [];
$res_attachments = $db->Execute("SELECT PK_EMAIL_ATTACHMENT, FILE_NAME, LOCATION FROM DOA_EMAIL_ATTACHMENT WHERE PK_EMAIL = '$draft_id'");
if ($res_attachments) {
    while (!$res_attachments->EOF) {
        $attachments[] = [
            'PK_EMAIL_ATTACHMENT' => (int)$res_attachments->fields['PK_EMAIL_ATTACHMENT'],
            'FILE_NAME' => $res_attachments->fields['FILE_NAME'],
            'LOCATION' => $res_attachments->fields['LOCATION']
        ];
        $res_attachments->MoveNext();
    }
}

echo json_encode([
    'success' => true,
    'draft_id' => (int)$draft->fields['PK_EMAIL'],
    'subject' => $draft->fields['SUBJECT'],
    'content' => $draft->fields['CONTENT'],
    'recipients' => $recipient_ids,
    'attachments' => $attachments
]);
