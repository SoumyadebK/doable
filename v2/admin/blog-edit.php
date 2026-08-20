<?php
/** Create or edit a blog post. */
require_once __DIR__ . '/auth.php';

$id = trim($_GET['id'] ?? '');
$post = [
    'id' => '', 'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '',
    'cover_image' => '', 'author' => 'Doable Team', 'category' => 'General',
    'tags' => '', 'published' => 0, 'published_at' => '',
];
$error = '';

if ($id !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = db()->prepare('SELECT * FROM blog_posts WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) { $post = $row; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $id          = trim($_POST['id'] ?? '');
        $title       = trim($_POST['title'] ?? '');
        $slug        = slugify(trim($_POST['slug'] ?? '') !== '' ? $_POST['slug'] : $title);
        $excerpt     = trim($_POST['excerpt'] ?? '');
        $body        = (string)($_POST['content'] ?? '');
        $cover       = trim($_POST['cover_image'] ?? '');
        $author      = trim($_POST['author'] ?? '') ?: 'Doable Team';
        $category    = trim($_POST['category'] ?? '') ?: 'General';
        $tags        = trim($_POST['tags'] ?? '');
        $published   = !empty($_POST['published']) ? 1 : 0;
        $publishedAt = trim($_POST['published_at'] ?? '');

        // keep form state for re-render on error
        $post = compact('id','title','slug','excerpt','cover','author','category','tags','published','publishedAt')
              + ['content' => $body, 'cover_image' => $cover, 'published_at' => $publishedAt];

        if ($title === '') {
            $error = 'Please enter a title.';
        } else {
            // default publish date when publishing for the first time
            $pubAtSql = null;
            if ($published) {
                $pubAtSql = $publishedAt !== '' ? date('Y-m-d H:i:s', strtotime($publishedAt)) : date('Y-m-d H:i:s');
            } elseif ($publishedAt !== '') {
                $pubAtSql = date('Y-m-d H:i:s', strtotime($publishedAt));
            }
            try {
                if ($id === '') {
                    $ins = db()->prepare('INSERT INTO blog_posts (id, slug, title, excerpt, content, cover_image, author, category, tags, published, published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                    $ins->execute([uuidv4(), $slug, $title, $excerpt, $body, $cover ?: null, $author, $category, $tags, $published, $pubAtSql]);
                } else {
                    $up = db()->prepare('UPDATE blog_posts SET slug=?, title=?, excerpt=?, content=?, cover_image=?, author=?, category=?, tags=?, published=?, published_at=? WHERE id=?');
                    $up->execute([$slug, $title, $excerpt, $body, $cover ?: null, $author, $category, $tags, $published, $pubAtSql, $id]);
                }
                header('Location: ' . $base . '/admin/blog.php?saved=1');
                exit;
            } catch (Throwable $ex) {
                error_log('blog save failed: ' . $ex->getMessage());
                $error = 'Could not save. The web address (slug) may already be in use — try a different title or slug.';
            }
        }
    }
}

$admin_page = 'blog'; $admin_title = ($post['id'] ? 'Edit' : 'New') . ' Post';
include __DIR__ . '/layout-header.php';
?>
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold text-gray-900"><?= $post['id'] ? 'Edit Post' : 'New Post' ?></h1>
  <a href="<?= $base ?>/admin/blog.php" class="text-sm text-gray-500 hover:text-emerald-600">&larr; Back to posts</a>
</div>

<?php if ($error): ?>
  <div class="mb-6 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST" class="space-y-5 bg-white rounded-xl border border-gray-100 shadow-sm p-6">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e($post['id']) ?>">

  <div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
    <input name="title" required value="<?= e($post['title']) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Web address (slug)</label>
      <input name="slug" value="<?= e($post['slug']) ?>" placeholder="auto-generated from title" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
      <input name="category" value="<?= e($post['category']) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
    </div>
  </div>
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Excerpt (short summary)</label>
    <textarea name="excerpt" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"><?= e($post['excerpt']) ?></textarea>
  </div>
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Content (Markdown supported: ## heading, - list, **bold**)</label>
    <textarea name="content" rows="14" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 font-mono text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"><?= e($post['content']) ?></textarea>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Cover image URL (optional)</label>
      <input name="cover_image" value="<?= e($post['cover_image']) ?>" placeholder="https://i.ytimg.com/vi/rnJTwv71Yjc/maxresdefault.jpg" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Author</label>
      <input name="author" value="<?= e($post['author']) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
    </div>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Tags (comma-separated)</label>
      <input name="tags" value="<?= e($post['tags']) ?>" placeholder="scheduling, retention" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Publish date (optional)</label>
      <input type="date" name="published_at" value="<?= e($post['published_at'] ? date('Y-m-d', strtotime($post['published_at'])) : '') ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
    </div>
  </div>
  <label class="inline-flex items-center gap-2 text-sm text-gray-700">
    <input type="checkbox" name="published" value="1" <?= $post['published'] ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
    Published (visible on the website)
  </label>
  <div class="flex justify-end gap-3 pt-2">
    <a href="<?= $base ?>/admin/blog.php" class="px-5 py-2.5 rounded-full font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200">Cancel</a>
    <button type="submit" class="btn-premium">Save Post</button>
  </div>
</form>
<?php include __DIR__ . '/layout-footer.php'; ?>
