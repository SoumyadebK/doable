<?php
/** Blog admin: list all posts, delete. */
require_once __DIR__ . '/auth.php';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && csrf_check()) {
    try {
        $d = db()->prepare('DELETE FROM blog_posts WHERE id = ?');
        $d->execute([$_POST['id'] ?? '']);
    } catch (Throwable $ex) { error_log('blog delete failed: ' . $ex->getMessage()); }
    header('Location: ' . $base . '/admin/blog.php?deleted=1');
    exit;
}

$posts = [];
try { $posts = db()->query('SELECT id, title, slug, category, published, published_at, updated_at FROM blog_posts ORDER BY updated_at DESC')->fetchAll(); }
catch (Throwable $ex) { error_log($ex->getMessage()); }

$admin_page = 'blog'; $admin_title = 'Blog';
include __DIR__ . '/layout-header.php';
?>
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl font-bold text-gray-900">Blog Posts</h1>
    <p class="text-gray-500">Write and manage your articles.</p>
  </div>
  <a href="<?= $base ?>/admin/blog-edit.php" class="btn-premium">+ New Post</a>
</div>

<?php if (isset($_GET['deleted'])): ?>
  <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">Post deleted.</div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <?php if (!$posts): ?>
    <p class="p-8 text-center text-gray-500">No posts yet. Click &ldquo;New Post&rdquo; to write your first one.</p>
  <?php else: ?>
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-gray-500 text-left">
      <tr><th class="px-5 py-3 font-medium">Title</th><th class="px-5 py-3 font-medium">Category</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 font-medium">Date</th><th class="px-5 py-3"></th></tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
      <?php foreach ($posts as $p): ?>
      <tr>
        <td class="px-5 py-3 font-medium text-gray-900"><?= e($p['title']) ?></td>
        <td class="px-5 py-3 text-gray-600"><?= e($p['category']) ?></td>
        <td class="px-5 py-3">
          <?php if ($p['published']): ?><span class="inline-block px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">Published</span>
          <?php else: ?><span class="inline-block px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">Draft</span><?php endif; ?>
        </td>
        <td class="px-5 py-3 text-gray-500"><?= e($p['published_at'] ? date('M j, Y', strtotime($p['published_at'])) : '—') ?></td>
        <td class="px-5 py-3 text-right whitespace-nowrap">
          <a href="<?= $base ?>/admin/blog-edit.php?id=<?= urlencode($p['id']) ?>" class="text-emerald-600 font-semibold hover:underline">Edit</a>
          <form method="POST" class="inline" onsubmit="return confirm('Delete this post? This cannot be undone.');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
            <button class="ml-3 text-red-500 font-semibold hover:underline">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/layout-footer.php'; ?>
