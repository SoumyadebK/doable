<?php
/** Public single blog post by ?slug=. */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/markdown.php';
$base = base_path();

$slug = trim($_GET['slug'] ?? '');
$post = null;
if ($slug !== '') {
    try {
        $stmt = db()->prepare('SELECT * FROM blog_posts WHERE slug = :slug AND published = 1 LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $post = $stmt->fetch();
    } catch (Throwable $ex) {
        error_log('blog post failed: ' . $ex->getMessage());
    }
}

if (!$post) {
    http_response_code(404);
    $page_title = 'Post not found | ' . SITE_NAME;
    include __DIR__ . '/includes/header.php';
    echo '<section class="pt-40 pb-24 text-center px-4"><h1 class="text-3xl font-bold text-gray-900 mb-4">Post not found</h1>'
       . '<p class="text-gray-600 mb-6">That article may have moved or is no longer available.</p>'
       . '<a href="' . $base . '/blog.php" class="btn-premium">Back to the blog</a></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$page = 'blog';
$page_title = $post['title'] . ' | ' . SITE_NAME;
$tags = array_filter(array_map('trim', explode(',', $post['tags'] ?? '')));
include __DIR__ . '/includes/header.php';
?>
<article class="pt-32 pb-20 px-4 sm:px-6 lg:px-8">
  <div class="max-w-3xl mx-auto">
    <a href="<?= $base ?>/blog.php" class="text-emerald-600 font-semibold text-sm">&larr; Back to the blog</a>
    <div class="flex items-center gap-2 text-sm text-emerald-600 font-semibold mt-6 mb-3">
      <span><?= e($post['category']) ?></span>
      <?php if (!empty($post['published_at'])): ?><span class="text-gray-400">&middot; <?= e(date('M j, Y', strtotime($post['published_at']))) ?></span><?php endif; ?>
      <span class="text-gray-400">&middot; <?= e($post['author']) ?></span>
    </div>
    <h1 class="text-premium-heading text-3xl md:text-4xl font-extrabold mb-6"><?= e($post['title']) ?></h1>
    <?php if (!empty($post['cover_image'])): ?>
      <div class="aspect-video rounded-2xl overflow-hidden bg-gray-100 mb-8 relative">
        <img src="<?= e($post['cover_image']) ?>" alt="<?= e($post['title']) ?>" class="absolute inset-0 w-full h-full object-cover">
      </div>
    <?php endif; ?>
    <div class="prose-doable"><?= markdown_to_html($post['content']) ?></div>
    <?php if ($tags): ?>
      <div class="flex flex-wrap gap-2 mt-10 pt-6 border-t border-gray-100">
        <?php foreach ($tags as $t): ?><span class="text-xs font-medium px-3 py-1 rounded-full bg-emerald-50 text-emerald-700">#<?= e($t) ?></span><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="mt-12 card-premium p-8 text-center bg-gradient-to-br from-emerald-50 to-teal-50">
      <h3 class="text-xl font-bold text-gray-900 mb-2">Ready to run your business effortlessly?</h3>
      <p class="text-gray-600 mb-4">Start your 30-day free trial today.</p>
      <a href="<?= $base ?>/index.php#contact" class="btn-premium">Start Free Trial</a>
    </div>
  </div>
</article>
<?php include __DIR__ . '/includes/footer.php'; ?>
