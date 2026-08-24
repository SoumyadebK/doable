<?php
require_once('../global/config.php');
global $db;
global $db_account;

$title = empty($_GET['id']) ? "Add Blog Post" : "Edit Blog Post";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$PK_BLOG = 0;
$TITLE = '';
$SLUG = '';
$EXCERPT = '';
$CONTENT = '';
$FEATURED_IMAGE_URL = '';
$AUTHOR_NAME = '';
$CATEGORY = '';
$TAGS = [];
$STATUS = 1;
$SEO_TITLE = '';
$SEO_DESCRIPTION = '';
$CANONICAL_URL = '';
$PUBLISHED_AT = '';
$COMMENTS_ENABLED = 1;
$EXTERNAL_SOURCE = '';
$EXTERNAL_SOURCE_ID = '';

if (!empty($_GET['id'])) {
    $res = $db->Execute("SELECT * FROM `DOA_BLOGS` WHERE `PK_BLOG` = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_blogs.php");
        exit;
    }
    $PK_BLOG = $_GET['id'];
    $TITLE = $res->fields['TITLE'];
    $SLUG = $res->fields['SLUG'];
    $EXCERPT = $res->fields['EXCERPT'];
    $CONTENT = $res->fields['CONTENT'];
    $FEATURED_IMAGE_URL = $res->fields['FEATURED_IMAGE_URL'];
    $AUTHOR_NAME = $res->fields['AUTHOR_NAME'];
    $CATEGORY = $res->fields['CATEGORY'];
    $TAGS = json_decode($res->fields['TAGS'], true) ?: [];
    $STATUS = $res->fields['STATUS'];
    $SEO_TITLE = $res->fields['SEO_TITLE'];
    $SEO_DESCRIPTION = $res->fields['SEO_DESCRIPTION'];
    $CANONICAL_URL = $res->fields['CANONICAL_URL'];
    $PUBLISHED_AT = $res->fields['PUBLISHED_AT'];
    $COMMENTS_ENABLED = $res->fields['COMMENTS_ENABLED'];
    $EXTERNAL_SOURCE = $res->fields['EXTERNAL_SOURCE'];
    $EXTERNAL_SOURCE_ID = $res->fields['EXTERNAL_SOURCE_ID'];
}

if (!empty($_POST)) {
    $BLOG_DATA = array();
    $BLOG_DATA['TITLE'] = $_POST['TITLE'];
    $BLOG_DATA['SLUG'] = generateSlug($_POST['TITLE'], $db);
    $BLOG_DATA['EXCERPT'] = $_POST['EXCERPT'];
    $BLOG_DATA['CONTENT'] = $_POST['CONTENT'];
    $BLOG_DATA['FEATURED_IMAGE_URL'] = $_POST['FEATURED_IMAGE_URL'];
    $BLOG_DATA['AUTHOR_NAME'] = $_POST['AUTHOR_NAME'];
    $BLOG_DATA['CATEGORY'] = $_POST['CATEGORY'];
    $BLOG_DATA['TAGS'] = json_encode(array_filter(explode(',', $_POST['TAGS'])));
    $BLOG_DATA['STATUS'] = $_POST['STATUS'];
    $BLOG_DATA['SEO_TITLE'] = $_POST['SEO_TITLE'];
    $BLOG_DATA['SEO_DESCRIPTION'] = $_POST['SEO_DESCRIPTION'];
    $BLOG_DATA['CANONICAL_URL'] = $_POST['CANONICAL_URL'];
    $BLOG_DATA['PUBLISHED_AT'] = !empty($_POST['PUBLISHED_AT']) ? date('Y-m-d H:i:s', strtotime($_POST['PUBLISHED_AT'])) : null;
    $BLOG_DATA['COMMENTS_ENABLED'] = isset($_POST['COMMENTS_ENABLED']) ? 1 : 0;
    $BLOG_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
    $BLOG_DATA['EDITED_ON'] = date("Y-m-d H:i");

    if (empty($_GET['id'])) {
        $BLOG_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $BLOG_DATA['CREATED_ON'] = date("Y-m-d H:i");
        $BLOG_DATA['ACTIVE'] = 1;
        db_perform('DOA_BLOGS', $BLOG_DATA, 'insert');
        $PK_BLOG = $db->Insert_ID();
    } else {
        db_perform('DOA_BLOGS', $BLOG_DATA, 'update', " PK_BLOG =  '$_GET[id]'");
        $PK_BLOG = $_GET['id'];
    }

    header("location:all_blogs.php");
    exit;
}

function generateSlug($title, $db)
{
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
    $original_slug = $slug;
    $counter = 1;

    $current_id = !empty($_GET['id']) ? (int)$_GET['id'] : 0;

    while ($db->GetOne("
        SELECT COUNT(*)
        FROM DOA_BLOGS
        WHERE SLUG = '$slug'
        AND PK_BLOG != $current_id
    ")) {
        $slug = $original_slug . '-' . $counter++;
    }

    return $slug;
}

// Get categories for dropdown
$categories = [];

$category_result = $db->Execute("
    SELECT DISTINCT CATEGORY
    FROM DOA_BLOGS
    WHERE CATEGORY IS NOT NULL
      AND CATEGORY != ''
    ORDER BY CATEGORY
");

while (!$category_result->EOF) {
    $categories[] = $category_result->fields['CATEGORY'];
    $category_result->MoveNext();
}

$statuses = array(
    1 => 'Draft',
    2 => 'Published',
    3 => 'Archived'
);
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary-color: #39B54A;
        --primary-dark: #2D8F3B;
        --primary-rgb: 57, 181, 74;
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
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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

    .container-fluid {
        padding: 24px 32px !important;
        max-width: 1400px;
        margin: 0 auto;
    }

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

    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    textarea.form-control-modern {
        min-height: 100px;
        resize: vertical;
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
        accent-color: var(--primary-color);
        cursor: pointer;
        flex-shrink: 0;
    }

    .checkbox-group-modern {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
        padding: 4px 0;
    }

    .checkbox-group-modern input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-color);
        cursor: pointer;
        flex-shrink: 0;
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

    .btn-modern-secondary {
        background: var(--gray-100);
        color: var(--gray-700);
    }

    .btn-modern-secondary:hover {
        background: var(--gray-200);
        color: var(--gray-800);
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

    .full-width {
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .full-width {
            grid-column: 1;
        }
    }

    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
    }

    /* Quill Editor */
    .quill-wrapper {
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .quill-wrapper:focus-within {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .quill-wrapper .ql-toolbar {
        border: none;
        border-bottom: 1px solid var(--gray-200);
        background: var(--gray-50);
        padding: 8px 12px;
    }

    .quill-wrapper .ql-container {
        border: none;
        font-size: 14px;
        font-family: inherit;
        min-height: 300px;
    }

    .quill-wrapper .ql-editor {
        min-height: 300px;
        padding: 16px;
        font-size: 14px;
        line-height: 1.8;
    }

    .quill-wrapper .ql-editor p {
        margin-bottom: 8px;
    }

    /* Status indicator */
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

    /* Comments Table */
    .table-modern {
        width: 100% !important;
        border-collapse: collapse;
        font-size: 14px;
    }

    .table-modern thead th {
        background: var(--gray-50);
        padding: 10px 14px;
        text-align: left;
        font-weight: 600;
        color: var(--gray-600);
        border-bottom: 2px solid var(--gray-200);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .table-modern tbody td {
        padding: 10px 14px;
        border-bottom: 1px solid var(--gray-100);
        color: var(--gray-700);
        vertical-align: middle;
    }

    .table-modern tbody tr:hover {
        background: var(--gray-50);
    }

    .status-badge {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-badge.success {
        background: #D1FAE5;
        color: #065F46;
    }

    .status-badge.warning {
        background: #FEF3C7;
        color: #92400E;
    }

    .status-badge.danger {
        background: #FEE2E2;
        color: #991B1B;
    }

    .action-icons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-modern-danger {
        background: var(--danger-color);
        color: #fff;
    }

    .btn-modern-danger:hover {
        background: #DC2626;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid body_content" style="margin-top: 0px !important;">

                <div class="breadcrumb-wrapper">
                    <h4>
                        <i class="fas fa-blog"></i>
                        <?= $title ?>
                    </h4>
                    <nav class="breadcrumb-nav">
                        <a href="setup.php">Setup</a>
                        <span class="separator">/</span>
                        <a href="all_blogs.php">All Blogs</a>
                        <span class="separator">/</span>
                        <span class="current"><?= $title ?></span>
                    </nav>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card-modern">
                            <div class="card-header">
                                <h5>
                                    <i class="fas fa-edit"></i>
                                    <?= !empty($_GET['id']) ? 'Edit Blog Post' : 'Create New Blog Post' ?>
                                </h5>
                                <?php if (!empty($_GET['id'])): ?>
                                    <span class="status-indicator <?= ($STATUS == 2) ? 'active' : 'inactive' ?>">
                                        <i class="fas fa-circle"></i>
                                        <?= $statuses[$STATUS] ?? 'Draft' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <form id="blog_form" action="" method="post" enctype="multipart/form-data">

                                    <div class="form-grid">
                                        <!-- Title -->
                                        <div class="form-group-modern full-width">
                                            <label class="form-label">Title <span class="required">*</span></label>
                                            <input type="text" id="TITLE" name="TITLE" class="form-control-modern" placeholder="Enter blog title" value="<?= htmlspecialchars($TITLE) ?>" required oninput="generateSlugFromTitle(this.value)">
                                            <div class="form-helper">The main heading of your blog post</div>
                                        </div>

                                        <!-- Slug -->
                                        <div class="form-group-modern full-width">
                                            <label class="form-label">Slug <span class="required">*</span></label>
                                            <input type="text" id="SLUG" name="SLUG" class="form-control-modern" placeholder="auto-generated-slug" value="<?= htmlspecialchars($SLUG) ?>" required>
                                            <div class="form-helper">URL-friendly version of the title. Auto-generated but can be edited.</div>
                                        </div>

                                        <!-- Excerpt -->
                                        <div class="form-group-modern full-width">
                                            <label class="form-label">Excerpt</label>
                                            <textarea id="EXCERPT" name="EXCERPT" class="form-control-modern" rows="3" placeholder="Brief summary of the blog post"><?= htmlspecialchars($EXCERPT) ?></textarea>
                                            <div class="form-helper">A short summary shown in blog listings</div>
                                        </div>

                                        <!-- Content -->
                                        <div class="form-group-modern full-width">
                                            <label class="form-label">Content <span class="required">*</span></label>
                                            <div class="quill-wrapper">
                                                <div id="editor"></div>
                                            </div>
                                            <input type="hidden" name="CONTENT" id="CONTENT">
                                            <div class="form-helper">The main content of your blog post</div>
                                        </div>

                                        <!-- Featured Image -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Featured Image URL</label>
                                            <input type="text" id="FEATURED_IMAGE_URL" name="FEATURED_IMAGE_URL" class="form-control-modern" placeholder="https://example.com/image.jpg" value="<?= htmlspecialchars($FEATURED_IMAGE_URL) ?>">
                                            <div class="form-helper">URL of the featured image</div>
                                        </div>

                                        <!-- Author -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Author Name</label>
                                            <input type="text" id="AUTHOR_NAME" name="AUTHOR_NAME" class="form-control-modern" placeholder="Author name" value="<?= htmlspecialchars($AUTHOR_NAME) ?>">
                                            <div class="form-helper">Name of the blog post author</div>
                                        </div>

                                        <!-- Category -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Category</label>
                                            <input type="text" id="CATEGORY" name="CATEGORY" class="form-control-modern" placeholder="Category" value="<?= htmlspecialchars($CATEGORY) ?>" list="category-list">
                                            <datalist id="category-list">
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= htmlspecialchars($cat) ?>">
                                                    <?php endforeach; ?>
                                            </datalist>
                                            <div class="form-helper">Blog post category</div>
                                        </div>

                                        <!-- Tags -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Tags</label>
                                            <input type="text" id="TAGS" name="TAGS" class="form-control-modern" placeholder="tag1, tag2, tag3" value="<?= htmlspecialchars(implode(', ', $TAGS)) ?>">
                                            <div class="form-helper">Comma-separated tags</div>
                                        </div>

                                        <!-- Status -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Status <span class="required">*</span></label>
                                            <select id="STATUS" name="STATUS" class="form-control-modern" required>
                                                <option value="1" <?= ($STATUS == 1) ? 'selected' : '' ?>>Draft</option>
                                                <option value="2" <?= ($STATUS == 2) ? 'selected' : '' ?>>Published</option>
                                                <option value="3" <?= ($STATUS == 3) ? 'selected' : '' ?>>Archived</option>
                                            </select>
                                            <div class="form-helper">Current status of the blog post</div>
                                        </div>

                                        <!-- Published At -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Published Date</label>
                                            <input type="datetime-local" id="PUBLISHED_AT" name="PUBLISHED_AT" class="form-control-modern" value="<?= !empty($PUBLISHED_AT) ? date('Y-m-d\TH:i', strtotime($PUBLISHED_AT)) : '' ?>">
                                            <div class="form-helper">When the post should be published</div>
                                        </div>

                                        <!-- Comments Enabled -->
                                        <div class="form-group-modern">
                                            <label class="checkbox-group-modern" style="margin-top: 24px;">
                                                <input type="checkbox" id="COMMENTS_ENABLED" name="COMMENTS_ENABLED" value="1" <?= ($COMMENTS_ENABLED == 1) ? 'checked' : '' ?>>
                                                <span>Enable Comments</span>
                                            </label>
                                            <div class="form-helper">Allow readers to comment on this post</div>
                                        </div>

                                        <!-- SEO Title -->
                                        <div class="form-group-modern">
                                            <label class="form-label">SEO Title</label>
                                            <input type="text" id="SEO_TITLE" name="SEO_TITLE" class="form-control-modern" placeholder="SEO title" value="<?= htmlspecialchars($SEO_TITLE) ?>">
                                            <div class="form-helper">Title for search engines (max 60 characters)</div>
                                        </div>

                                        <!-- SEO Description -->
                                        <div class="form-group-modern">
                                            <label class="form-label">SEO Description</label>
                                            <textarea id="SEO_DESCRIPTION" name="SEO_DESCRIPTION" class="form-control-modern" rows="2" placeholder="SEO description"><?= htmlspecialchars($SEO_DESCRIPTION) ?></textarea>
                                            <div class="form-helper">Description for search engines (max 160 characters)</div>
                                        </div>

                                        <!-- Canonical URL -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Canonical URL</label>
                                            <input type="text" id="CANONICAL_URL" name="CANONICAL_URL" class="form-control-modern" placeholder="https://example.com/canonical-url" value="<?= htmlspecialchars($CANONICAL_URL) ?>">
                                            <div class="form-helper">Canonical URL for SEO</div>
                                        </div>
                                    </div>

                                    <!-- Comments Section -->
                                    <div class="form-group-modern full-width" style="margin-top: 20px; border-top: 1px solid var(--gray-200); padding-top: 20px;">
                                        <div style="font-size: 16px; font-weight: 600; color: var(--gray-700); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                                            <i class="fas fa-comments" style="color: var(--primary-color);"></i>
                                            Comments
                                            <?php if (!empty($_GET['id'])): ?>
                                                <span style="background: var(--gray-200); color: var(--gray-600); padding: 2px 12px; border-radius: 50px; font-size: 13px; font-weight: 500;">
                                                    <?php
                                                    $comment_count = $db->GetOne("SELECT COUNT(*) FROM BLOG_COMMENTS WHERE PK_BLOG = '$_GET[id]' AND STATUS = 1");
                                                    echo $comment_count ?: 0;
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($_GET['id'])): ?>
                                            <div class="table-responsive" style="overflow-x: auto;">
                                                <table class="table-modern">
                                                    <thead>
                                                        <tr>
                                                            <th>Name</th>
                                                            <th>Comment</th>
                                                            <th>Status</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $comments = $db->Execute("SELECT * FROM BLOG_COMMENTS WHERE PK_BLOG = '$_GET[id]' ORDER BY CREATED_ON DESC");
                                                        while (!$comments->EOF):
                                                            $status_labels = ['Pending', 'Approved', 'Rejected', 'Spam'];
                                                            $status_classes = ['warning', 'success', 'danger', 'danger'];
                                                            $status = $comments->fields['STATUS'];
                                                        ?>
                                                            <tr>
                                                                <td>
                                                                    <strong><?= htmlspecialchars($comments->fields['COMMENTER_NAME']) ?></strong>
                                                                    <br><small style="color: var(--gray-400);"><?= htmlspecialchars($comments->fields['COMMENTER_EMAIL']) ?></small>
                                                                </td>
                                                                <td>
                                                                    <?= nl2br(htmlspecialchars($comments->fields['COMMENT'])) ?>
                                                                    <br><small style="color: var(--gray-400); font-size: 11px;"><?= date('M d, Y H:i', strtotime($comments->fields['CREATED_ON'])) ?></small>
                                                                </td>
                                                                <td>
                                                                    <span class="status-badge <?= $status_classes[$status] ?? 'warning' ?>">
                                                                        <?= $status_labels[$status] ?? 'Pending' ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <div class="action-icons">
                                                                        <?php if ($status == 0): ?>
                                                                            <button class="btn-modern btn-modern-primary btn-modern-sm" onclick="updateCommentStatus(<?= $comments->fields['PK_COMMENT'] ?>, 1)">Approve</button>
                                                                        <?php endif; ?>
                                                                        <?php if ($status != 2): ?>
                                                                            <button class="btn-modern btn-modern-secondary btn-modern-sm" onclick="updateCommentStatus(<?= $comments->fields['PK_COMMENT'] ?>, 2)">Reject</button>
                                                                        <?php endif; ?>
                                                                        <?php if ($status != 3): ?>
                                                                            <button class="btn-modern btn-modern-secondary btn-modern-sm" onclick="updateCommentStatus(<?= $comments->fields['PK_COMMENT'] ?>, 3)">Spam</button>
                                                                        <?php endif; ?>
                                                                        <button class="btn-modern btn-modern-danger btn-modern-sm" onclick="deleteComment(<?= $comments->fields['PK_COMMENT'] ?>)">Delete</button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php $comments->MoveNext();
                                                        endwhile; ?>
                                                        <?php if ($comments->RecordCount() == 0): ?>
                                                            <tr>
                                                                <td colspan="4" style="text-align: center; padding: 32px; color: var(--gray-400);">No comments yet.</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <p style="color: var(--gray-400); padding: 16px 0;">Save the blog post first to manage comments.</p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-actions">
                                        <button type="submit" class="btn-modern btn-modern-primary">
                                            <i class="fas fa-save"></i>
                                            <?= empty($_GET['id']) ? 'Create Blog Post' : 'Update Blog Post' ?>
                                        </button>
                                        <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_blogs.php'">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
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

    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <script>
        // Initialize Quill Editor
        const quill = new Quill('#editor', {
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['link', 'image', 'video'],
                    [{
                        'header': 1
                    }, {
                        'header': 2
                    }],
                    [{
                        'list': 'ordered'
                    }, {
                        'list': 'bullet'
                    }],
                    [{
                        'script': 'sub'
                    }, {
                        'script': 'super'
                    }],
                    [{
                        'indent': '-1'
                    }, {
                        'indent': '+1'
                    }],
                    [{
                        'header': [1, 2, 3, 4, 5, 6, false]
                    }],
                    [{
                        'color': []
                    }, {
                        'background': []
                    }],
                    [{
                        'align': []
                    }],
                    ['clean']
                ]
            },
            theme: 'snow',
            placeholder: 'Write your blog content here...'
        });

        // Load existing content
        const content = <?= json_encode($CONTENT) ?>;
        if (content) {
            quill.root.innerHTML = content;
            document.getElementById('CONTENT').value = content;
        }

        quill.on('text-change', function() {
            document.getElementById('CONTENT').value = quill.root.innerHTML;
        });

        // Generate slug from title
        function generateSlugFromTitle(title) {
            const slugInput = document.getElementById('SLUG');
            if (!slugInput.value || slugInput.dataset.autoGenerated === 'true') {
                const slug = title.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
                slugInput.dataset.autoGenerated = 'true';
            }
        }

        // Mark slug as manually edited
        document.getElementById('SLUG').addEventListener('input', function() {
            this.dataset.autoGenerated = 'false';
        });

        // Initialize slug auto-generation flag
        document.getElementById('SLUG').dataset.autoGenerated = 'true';

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('blog_form');
            form.addEventListener('submit', function(e) {
                const title = document.getElementById('TITLE');
                const slug = document.getElementById('SLUG');
                const content = document.getElementById('CONTENT');

                let isValid = true;

                if (!title.value.trim()) {
                    title.classList.add('is-invalid');
                    isValid = false;
                } else {
                    title.classList.remove('is-invalid');
                }

                if (!slug.value.trim()) {
                    slug.classList.add('is-invalid');
                    isValid = false;
                } else {
                    slug.classList.remove('is-invalid');
                }

                if (!content.value.trim() || content.value === '<p><br></p>') {
                    document.querySelector('.quill-wrapper').style.borderColor = 'var(--danger-color)';
                    isValid = false;
                } else {
                    document.querySelector('.quill-wrapper').style.borderColor = 'var(--gray-200)';
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });
        });

        // Comment functions
        function updateCommentStatus(commentId, status) {
            if (confirm('Are you sure you want to update this comment?')) {
                $.ajax({
                    url: 'ajax/blog_ajax.php',
                    type: 'POST',
                    data: {
                        action: 'update_comment_status',
                        comment_id: commentId,
                        status: status,
                        blog_id: <?= $_GET['id'] ?? 0 ?>
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            }
        }

        function deleteComment(commentId) {
            if (confirm('Are you sure you want to delete this comment?')) {
                $.ajax({
                    url: 'ajax/blog_ajax.php',
                    type: 'POST',
                    data: {
                        action: 'delete_comment',
                        comment_id: commentId,
                        blog_id: <?= $_GET['id'] ?? 0 ?>
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            }
        }
    </script>
</body>

</html>