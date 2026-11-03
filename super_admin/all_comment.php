<?php
require_once('../global/config.php');
$title = "All Comments";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1) {
    header("location:../login.php");
    exit;
}

$PK_BLOG = $_GET['id'];
$blog_data = $db->Execute("SELECT * FROM `DOA_BLOGS` WHERE PK_BLOG = '$PK_BLOG'");
$BLOG_TITLE = $blog_data->fields['TITLE'];

$blog_comment_data = $db->Execute("SELECT * FROM DOA_BLOG_COMMENTS WHERE PK_BLOG = '$PK_BLOG' AND PARENT_COMMENT_ID = 0 AND STATUS = 0 AND ACTIVE = 1 ORDER BY CREATED_ON ASC");

// Helper to build a set of initials from a commenter's name, used for the avatar badge.
function get_initials($name)
{
    $name = trim($name);
    if ($name === '') return '?';
    $parts = preg_split('/\s+/', $name);
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initials .= strtoupper(substr(end($parts), 0, 1));
    }
    return $initials;
}

if (isset($_GET['action']) && ($_GET['action'] == 'accept' || $_GET['action'] == 'reject')) {
    $action = ($_GET['action'] == 'accept') ? 1 : 2;
    $id = $_GET['comment_id'];
    if (empty($id)) {
        header("location:../login.php");
        exit;
    }

    $db->Execute("UPDATE DOA_BLOG_COMMENTS SET STATUS = $action WHERE PK_BLOG_COMMENT = $id");

    header("location:all_comment.php?id=$PK_BLOG");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

<style>
    /* ===== Comments section ===== */
    .comments-container {
        margin-top: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .comment-card {
        background: #fff;
        border: 1px solid #edf0f5;
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .comment-card:hover {
        box-shadow: 0 4px 14px rgba(16, 24, 40, 0.08);
        border-color: #e3e8f0;
    }

    .comment-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.6rem;
    }

    .commenter-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .avatar-badge {
        flex-shrink: 0;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6a5af9, #8f7bff);
        color: #fff;
        font-weight: 600;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        letter-spacing: 0.02em;
    }

    .avatar-badge.reply-avatar {
        width: 34px;
        height: 34px;
        font-size: 0.75rem;
        background: linear-gradient(135deg, #9aa4b2, #c1c8d3);
    }

    .commenter-name {
        display: block;
        font-weight: 600;
        color: #1f2937;
        font-size: 0.95rem;
        line-height: 1.2;
    }

    .comment-date {
        display: block;
        font-size: 0.78rem;
        color: #94a3b8;
        margin-top: 2px;
    }

    .comment-text {
        color: #374151;
        font-size: 0.93rem;
        line-height: 1.55;
        margin: 0 0 0.9rem 0;
        padding-left: calc(22px + 0.25rem);
    }

    .reply-text {
        padding-left: calc(14px + 0.65rem);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        white-space: nowrap;
    }

    .status-badge.pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-badge.approved {
        background: #dcfce7;
        color: #166534;
    }

    .status-badge.rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .comment-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-left: calc(22px + 0.25rem);
    }

    .reply-actions {
        padding-left: calc(14px + 0.65rem);
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border: 1px solid transparent;
        border-radius: 8px;
        padding: 6px 14px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
    }

    .btn-accept {
        background: #ecfdf5;
        color: #059669;
        border-color: #a7f3d0;
    }

    .btn-accept:hover {
        background: #059669;
        color: #fff;
    }

    .btn-reject {
        background: #fef2f2;
        color: #dc2626;
        border-color: #fecaca;
    }

    .btn-reject:hover {
        background: #dc2626;
        color: #fff;
    }

    .btn-action svg {
        width: 14px;
        height: 14px;
    }

    .replies-container {
        margin-top: 1rem;
        margin-left: calc(22px + 0.25rem - 1px);
        padding-left: 1.25rem;
        border-left: 2px solid #eef1f6;
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }

    .reply-card {
        background: #f9fafb;
        border: 1px solid #eef1f6;
        border-radius: 12px;
        padding: 1rem 1.1rem;
        transition: background 0.2s ease;
    }

    .reply-card:hover {
        background: #f3f4f6;
    }

    .reply-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.45rem;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #94a3b8;
    }

    .empty-state svg {
        width: 46px;
        height: 46px;
        color: #cbd5e1;
        margin-bottom: 0.75rem;
    }
</style>

<body class="skin-default-dark fixed-layout">

    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <?php require_once('../includes/setup_menu_super_admin.php') ?>
            <div class="container-fluid body_content m-0">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item"><a href="all_blog.php">All Blogs</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><?= $BLOG_TITLE ?></h4>
                            </div>
                            <div class="card-body">
                                <?php if ($blog_comment_data->RecordCount() > 0): ?>
                                    <div id="commentsContainer" class="comments-container">
                                        <?php while (!$blog_comment_data->EOF):
                                            $comment_id = $blog_comment_data->fields['PK_BLOG_COMMENT'];
                                            $comment_status = (int) $blog_comment_data->fields['STATUS'];
                                            $status_label = $comment_status === 1 ? 'approved' : ($comment_status === 2 ? 'rejected' : 'pending');
                                        ?>

                                            <div class="comment-card" id="comment-<?= $comment_id ?>">
                                                <div class="comment-head">
                                                    <div class="commenter-info">
                                                        <div>
                                                            <span class="commenter-name"><?= htmlspecialchars($blog_comment_data->fields['COMMENTER_NAME']) ?></span>
                                                            <span class="comment-date"><?= date('M j, Y g:i a', strtotime($blog_comment_data->fields['CREATED_ON'])) ?></span>
                                                        </div>
                                                    </div>
                                                    <span class="status-badge <?= $status_label ?>"><?= ucfirst($status_label) ?></span>
                                                </div>

                                                <p class="comment-text"><?= nl2br(htmlspecialchars($blog_comment_data->fields['COMMENT'])) ?></p>

                                                <div class="comment-actions">
                                                    <a href="all_comment.php?id=<?= $PK_BLOG ?>&comment_id=<?= $comment_id ?>&action=accept" class="btn-action btn-accept">
                                                        <svg viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 111.4-1.4l3.8 3.79 6.8-6.79a1 1 0 011.4 0z" clip-rule="evenodd" />
                                                        </svg>
                                                        Accept
                                                    </a>
                                                    <a href="all_comment.php?id=<?= $PK_BLOG ?>&comment_id=<?= $comment_id ?>&action=reject" class="btn-action btn-reject">
                                                        <svg viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M4.3 4.3a1 1 0 011.4 0L10 8.6l4.3-4.3a1 1 0 111.4 1.4L11.4 10l4.3 4.3a1 1 0 01-1.4 1.4L10 11.4l-4.3 4.3a1 1 0 01-1.4-1.4L8.6 10 4.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd" />
                                                        </svg>
                                                        Reject
                                                    </a>
                                                </div>

                                                <?php
                                                $comment_replies = $db->Execute("SELECT * FROM DOA_BLOG_COMMENTS WHERE PK_BLOG = '" . $blog_data->fields['PK_BLOG'] . "' AND PARENT_COMMENT_ID = '" . $comment_id . "' AND STATUS = 0 AND ACTIVE = 1 ORDER BY CREATED_ON ASC");
                                                if ($comment_replies->RecordCount() > 0):
                                                ?>
                                                    <div class="replies-container">
                                                        <?php while (!$comment_replies->EOF):
                                                            $reply_id = $comment_replies->fields['PK_BLOG_COMMENT'];
                                                            $reply_status = (int) $comment_replies->fields['STATUS'];
                                                            $reply_status_label = $reply_status === 1 ? 'approved' : ($reply_status === 2 ? 'rejected' : 'pending');
                                                        ?>
                                                            <div class="reply-card" id="comment-<?= $reply_id ?>">
                                                                <div class="reply-head">
                                                                    <div class="commenter-info">
                                                                        <div>
                                                                            <span class="commenter-name"><?= htmlspecialchars($comment_replies->fields['COMMENTER_NAME']) ?></span>
                                                                            <span class="comment-date"><?= date('M j, Y g:i a', strtotime($comment_replies->fields['CREATED_ON'])) ?></span>
                                                                        </div>
                                                                    </div>
                                                                    <span class="status-badge <?= $reply_status_label ?>"><?= ucfirst($reply_status_label) ?></span>
                                                                </div>
                                                                <p class="comment-text reply-text"><?= nl2br(htmlspecialchars($comment_replies->fields['COMMENT'])) ?></p>
                                                                <div class="comment-actions reply-actions">
                                                                    <a href="all_comment.php?id=<?= $PK_BLOG ?>&comment_id=<?= $reply_id ?>&action=accept" class="btn-action btn-accept">
                                                                        <svg viewBox="0 0 20 20" fill="currentColor">
                                                                            <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 111.4-1.4l3.8 3.79 6.8-6.79a1 1 0 011.4 0z" clip-rule="evenodd" />
                                                                        </svg>
                                                                        Accept
                                                                    </a>
                                                                    <a href="all_comment.php?id=<?= $PK_BLOG ?>&comment_id=<?= $reply_id ?>&action=reject" class="btn-action btn-reject">
                                                                        <svg viewBox="0 0 20 20" fill="currentColor">
                                                                            <path fill-rule="evenodd" d="M4.3 4.3a1 1 0 011.4 0L10 8.6l4.3-4.3a1 1 0 111.4 1.4L11.4 10l4.3 4.3a1 1 0 01-1.4 1.4L10 11.4l-4.3 4.3a1 1 0 01-1.4-1.4L8.6 10 4.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd" />
                                                                        </svg>
                                                                        Reject
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        <?php $comment_replies->MoveNext();
                                                        endwhile; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php
                                            $blog_comment_data->MoveNext();
                                        endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8-1.06 0-2.077-.16-3.02-.457L3 21l1.5-4.5C3.55 15.14 3 13.62 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <p class="mb-0">No comments have been posted on this blog yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
    <script>
        // Sends an accept/reject decision for a comment or reply to the moderation
        // endpoint and updates the row in place once the server confirms it.
        function moderateItem(type, id, action) {
            const label = type === 'reply' ? 'reply' : 'comment';
            const verb = action === 'accept' ? 'accept' : 'reject';
            if (!confirm(`Are you sure you want to ${verb} this ${label}?`)) {
                return;
            }

            fetch('comment_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id=${encodeURIComponent(id)}&action=${encodeURIComponent(action)}&blog_id=<?= (int) $PK_BLOG ?>`
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.success) {
                        const badge = document.querySelector(`#comment-${id} .status-badge`);
                        if (badge) {
                            badge.classList.remove('pending', 'approved', 'rejected');
                            badge.classList.add(action === 'accept' ? 'approved' : 'rejected');
                            badge.textContent = action === 'accept' ? 'Approved' : 'Rejected';
                        }
                    } else {
                        alert((data && data.message) || 'Something went wrong. Please try again.');
                    }
                })
                .catch(() => alert('Could not reach the server. Please try again.'));
        }
    </script>
</body>

</html>