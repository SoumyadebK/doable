<?php
require_once('global/config.php');
$slug = trim($_GET['slug'] ?? '');

$blog_data = $db->Execute("SELECT * FROM DOA_BLOGS WHERE SLUG = '$slug' AND STATUS = 2 AND ACTIVE = 1 LIMIT 1");
$blog_comment_data = $db->Execute("SELECT * FROM DOA_BLOG_COMMENTS WHERE PK_BLOG = '" . $blog_data->fields['PK_BLOG'] . "' AND PARENT_COMMENT_ID = 0 AND STATUS = 1 AND ACTIVE = 1 ORDER BY CREATED_ON ASC");

require_once __DIR__ . '/v2/includes/functions.php';
$page = 'blog';
$page_title = 'Blog | ' . SITE_NAME . ' | ' . slugToText($slug);
$base = base_path();


if (!empty($_POST['COMMENT'])) {
    $insert_date['PARENT_COMMENT_ID'] = trim($_POST['PARENT_COMMENT_ID'] ?? 0);
    $insert_date['COMMENTER_NAME'] = trim($_POST['COMMENTER_NAME'] ?? '');
    $insert_date['COMMENTER_EMAIL'] = trim($_POST['COMMENTER_EMAIL'] ?? '');
    $insert_date['COMMENT'] = trim($_POST['COMMENT'] ?? '');
    $insert_date['PK_BLOG'] = $blog_data->fields['PK_BLOG'];
    $insert_date['STATUS'] = 1; // Pending approval
    $insert_date['ACTIVE'] = 1; // Approved
    $insert_date['CREATED_ON'] = date('Y-m-d H:i:s');

    if ($insert_date['COMMENTER_NAME'] && $insert_date['COMMENTER_EMAIL'] && $insert_date['COMMENT']) {
        // Insert the comment into the database
        db_perform('DOA_BLOG_COMMENTS', $insert_date, 'insert');
    }
    header('Location: ' . $base . '/blog-post.php?slug=' . $slug);
    exit;
}

function slugToText($slug)
{
    $text = str_replace('-', ' ', $slug);
    $text = ucwords($text);
    return $text;
}
include __DIR__ . '/v2/includes/header.php';
?>

<style>
    /* Additional styles for the comment section */
    .comment-item {
        transition: all 0.2s ease;
    }

    .comment-item:hover {
        background-color: #f8fafc;
    }

    .reply-item {
        transition: all 0.2s ease;
    }

    .reply-item:hover {
        background-color: #fafafa;
    }

    .reply-form textarea,
    .reply-form input {
        font-size: 0.875rem;
    }
</style>

<section class="py-16 px-4 sm:px-6 lg:px-8 bg-white">
    <article class="pt-32 pb-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <a href="<?= $base ?>/blog.php" class="text-emerald-600 font-semibold text-sm">&larr; Back to the blog</a>
            <div class="flex items-center gap-2 text-sm text-emerald-600 font-semibold mt-6 mb-3">
                <span><?= e($blog_data->fields['CATEGORY']) ?></span>
                <?php if (!empty($blog_data->fields['PUBLISHED_AT'])): ?><span class="text-gray-400">&middot; <?= e(date('M j, Y', strtotime($blog_data->fields['PUBLISHED_AT']))) ?></span><?php endif; ?>
                <span class="text-gray-400">&middot; <?= e($blog_data->fields['AUTHOR_NAME']) ?></span>
            </div>
            <h1 class="text-premium-heading text-3xl md:text-4xl font-extrabold mb-6"><?= e($blog_data->fields['TITLE']) ?></h1>
            <?php if (!empty($blog_data->fields['FEATURED_IMAGE_URL'])): ?>
                <div class="aspect-video rounded-2xl overflow-hidden bg-gray-100 mb-8 relative">
                    <img src="<?= e($blog_data->fields['FEATURED_IMAGE_URL']) ?>" alt="<?= e($blog_data->fields['TITLE']) ?>" class="absolute inset-0 w-full h-full object-cover">
                </div>
            <?php endif; ?>
            <div class="prose-doable"><?= $blog_data->fields['CONTENT'] ?></div>
            <?php
            $tags = array_filter(array_map('trim', explode(',', $blog_data->fields['TAGS'] ?? '')));
            if ($tags): ?>
                <div class="flex flex-wrap gap-2 mt-10 pt-6 border-t border-gray-100">
                    <?php foreach ($tags as $t): ?><span class="text-xs font-medium px-3 py-1 rounded-full bg-emerald-50 text-emerald-700">#<?= e($t) ?></span><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($blog_comment_data->RecordCount() > 0): ?>
                <!-- Comments List -->
                <div id="commentsContainer" class="space-y-6 mt-12">
                    <h2 class="text-2xl font-bold text-gray-900 mb-8">Comments</h2>
                    <!-- Example Comment -->
                    <?php while (!$blog_comment_data->EOF): ?>

                        <div class="comment-item bg-gray-50 rounded-xl p-6">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <span class="font-semibold text-gray-900"><?= e($blog_comment_data->fields['COMMENTER_NAME']) ?></span>
                                    <span class="text-sm text-gray-500 ml-3"><?= e(date('M j, Y g:i a', strtotime($blog_comment_data->fields['CREATED_ON']))) ?></span>
                                </div>
                                <button onclick="toggleReplyForm(this)"
                                    class="text-sm text-emerald-600 hover:text-emerald-800 font-medium">
                                    Reply
                                </button>
                            </div>
                            <p class="text-gray-700"><?= e($blog_comment_data->fields['COMMENT']) ?></p>

                            <!-- Reply Form (hidden by default) -->
                            <div class="reply-form hidden mt-4 pl-6 border-l-2 border-emerald-200">
                                <form class="replyForm" action="" method="POST">
                                    <input type="hidden" name="PARENT_COMMENT_ID" value="<?= e($blog_comment_data->fields['PK_BLOG_COMMENT']) ?>">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                                        <input type="text" placeholder="Your Name *" name="COMMENTER_NAME" required
                                            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-sm">
                                        <input type="email" placeholder="Your Email *" name="COMMENTER_EMAIL" required
                                            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-sm">
                                    </div>
                                    <textarea rows="2" placeholder="Write a reply..." name="COMMENT" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-sm resize-y mb-2"></textarea>
                                    <button type="submit"
                                        class="bg-emerald-600 text-white px-4 py-1.5 rounded-lg text-sm font-semibold hover:bg-emerald-700 transition">
                                        Post Reply
                                    </button>
                                    <button type="button" onclick="cancelReply(this)"
                                        class="text-gray-500 hover:text-gray-700 text-sm ml-2">
                                        Cancel
                                    </button>
                                </form>
                            </div>

                            <?php $comment_replies = $db->Execute("SELECT * FROM DOA_BLOG_COMMENTS WHERE PK_BLOG = '" . $blog_data->fields['PK_BLOG'] . "' AND PARENT_COMMENT_ID = '" . $blog_comment_data->fields['PK_BLOG_COMMENT'] . "' AND STATUS = 1 AND ACTIVE = 1 ORDER BY CREATED_ON ASC");
                            if ($comment_replies->RecordCount() > 0):
                                while (!$comment_replies->EOF): ?>
                                    <!-- Replies -->
                                    <div class="replies-container mt-4 pl-6 border-l-2 border-gray-200 space-y-4">
                                        <div class="reply-item bg-white rounded-lg p-4 shadow-sm">
                                            <div>
                                                <span class="font-semibold text-gray-900"><?= e($comment_replies->fields['COMMENTER_NAME']) ?></span>
                                                <span class="text-sm text-gray-500 ml-3"><?= e(date('M j, Y g:i a', strtotime($comment_replies->fields['CREATED_ON']))) ?></span>
                                            </div>
                                            <p class="text-gray-700 mt-1"><?= e($comment_replies->fields['COMMENT']) ?></p>
                                        </div>
                                    </div>
                                <? $comment_replies->MoveNext();
                                endwhile; ?>
                            <?php endif; ?>

                        </div>
                    <?php
                        $blog_comment_data->MoveNext();
                    endwhile; ?>

                </div>
            <?php endif; ?>

            <!-- COMMENT SECTION -->
            <div class="mt-16 border-t border-gray-200 pt-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-8">Post a Comment</h2>
                <!-- Comment Form -->
                <form action="" method="POST" class="mb-12">
                    <input type="hidden" name="PARENT_COMMENT_ID" value="0">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="commentName" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                            <input type="text" id="commentName" name="COMMENTER_NAME" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                        </div>
                        <div>
                            <label for="commentEmail" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" id="commentEmail" name="COMMENTER_EMAIL" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="commentContent" class="block text-sm font-medium text-gray-700 mb-1">Comment *</label>
                        <textarea id="commentContent" name="COMMENT" rows="4" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition resize-y"></textarea>
                    </div>
                    <button type="submit"
                        class="bg-emerald-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-emerald-700 transition duration-300">
                        Post Comment
                    </button>
                </form>
            </div>

            <div class="mt-12 card-premium p-8 text-center bg-gradient-to-br from-emerald-50 to-teal-50">
                <h3 class="text-xl font-bold text-gray-900 mb-2">Ready to run your business effortlessly?</h3>
                <p class="text-gray-600 mb-4">Start your 30-day free trial today.</p>
                <a href="<?= $base ?>/index.php#contact" class="btn-premium">Start Free Trial</a>
            </div>
        </div>
    </article>
</section>

<?php include __DIR__ . '/v2/includes/footer.php'; ?>

<script>
    // Toggle reply form visibility
    function toggleReplyForm(button) {
        const commentItem = button.closest('.comment-item');
        const replyForm = commentItem.querySelector('.reply-form');
        // Hide all other reply forms
        document.querySelectorAll('.reply-form').forEach(form => {
            if (form !== replyForm) form.classList.add('hidden');
        });
        replyForm.classList.toggle('hidden');
    }

    // Cancel reply
    function cancelReply(button) {
        const replyForm = button.closest('.reply-form');
        replyForm.classList.add('hidden');
        // Clear the form
        replyForm.querySelectorAll('input, textarea').forEach(input => {
            if (input.type !== 'hidden') input.value = '';
        });
    }

    // Handle comment form submission
    document.getElementById('commentForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const name = document.getElementById('commentName').value.trim();
        const email = document.getElementById('commentEmail').value.trim();
        const content = document.getElementById('commentContent').value.trim();

        if (!name || !email || !content) {
            alert('Please fill in all fields.');
            return;
        }

        // Here you would send the comment to your server via AJAX
        // For now, we'll add it to the page
        addComment(name, email, content, null);

        // Reset form
        this.reset();
    });

    // Handle reply form submissions
    /* document.querySelectorAll('.replyForm').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const inputs = this.querySelectorAll('input:not([type="hidden"]), textarea');
            const name = inputs[0].value.trim();
            const email = inputs[1].value.trim();
            const content = inputs[2].value.trim();
            const parentId = this.querySelector('.parent_id').value;

            if (!name || !email || !content) {
                alert('Please fill in all fields.');
                return;
            }

            // Here you would send the reply to your server via AJAX
            // For now, we'll add it to the page
            addReply(this, name, content);

            // Reset form and hide it
            this.querySelectorAll('input:not([type="hidden"]), textarea').forEach(input => input.value = '');
            this.closest('.reply-form').classList.add('hidden');
        });
    }); */

    // Function to add a new comment (frontend only - for demo)
    function addComment(name, email, content, parentId = null) {
        const container = document.getElementById('commentsContainer');
        const now = new Date();
        const dateStr = now.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });

        const commentHtml = `
        <div class="comment-item bg-gray-50 rounded-xl p-6">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <span class="font-semibold text-gray-900">${escapeHtml(name)}</span>
                    <span class="text-sm text-gray-500 ml-3">${dateStr}</span>
                </div>
                <button onclick="toggleReplyForm(this)" 
                        class="text-sm text-emerald-600 hover:text-emerald-800 font-medium">
                    Reply
                </button>
            </div>
            <p class="text-gray-700">${escapeHtml(content)}</p>
            
            <div class="reply-form hidden mt-4 pl-6 border-l-2 border-emerald-200">
                <form class="replyForm">
                    <input type="hidden" class="parent_id" value="${Date.now()}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <input type="text" placeholder="Your Name *" required 
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-sm">
                        <input type="email" placeholder="Your Email *" required 
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-sm">
                    </div>
                    <textarea rows="2" placeholder="Write a reply..." required 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-sm resize-y mb-2"></textarea>
                    <button type="submit" 
                            class="bg-emerald-600 text-white px-4 py-1.5 rounded-lg text-sm font-semibold hover:bg-emerald-700 transition">
                        Post Reply
                    </button>
                    <button type="button" onclick="cancelReply(this)" 
                            class="text-gray-500 hover:text-gray-700 text-sm ml-2">
                        Cancel
                    </button>
                </form>
            </div>
            
            <div class="replies-container mt-4 pl-6 border-l-2 border-gray-200 space-y-4"></div>
        </div>
    `;

        container.insertAdjacentHTML('afterbegin', commentHtml);

        // Add event listener to the new reply form
        const newComment = container.firstElementChild;
        const replyForm = newComment.querySelector('.replyForm');
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const inputs = this.querySelectorAll('input:not([type="hidden"]), textarea');
            const name = inputs[0].value.trim();
            const email = inputs[1].value.trim();
            const content = inputs[2].value.trim();
            if (!name || !email || !content) {
                alert('Please fill in all fields.');
                return;
            }
            addReply(this, name, content);
            this.querySelectorAll('input:not([type="hidden"]), textarea').forEach(input => input.value = '');
            this.closest('.reply-form').classList.add('hidden');
        });
    }

    // Function to add a reply (frontend only - for demo)
    function addReply(form, name, content) {
        const commentItem = form.closest('.comment-item');
        const repliesContainer = commentItem.querySelector('.replies-container');
        const now = new Date();
        const dateStr = now.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });

        const replyHtml = `
        <div class="reply-item bg-white rounded-lg p-4 shadow-sm">
            <div>
                <span class="font-semibold text-gray-900">${escapeHtml(name)}</span>
                <span class="text-sm text-gray-500 ml-3">${dateStr}</span>
            </div>
            <p class="text-gray-700 mt-1">${escapeHtml(content)}</p>
        </div>
    `;

        repliesContainer.insertAdjacentHTML('beforeend', replyHtml);
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Note: In production, you should:
    // 1. Store comments in a database
    // 2. Use AJAX to submit comments to your server
    // 3. Implement proper authentication if needed
    // 4. Add CSRF protection
    // 5. Validate and sanitize all input on the server side
</script>