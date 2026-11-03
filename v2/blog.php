<?php
/** Public blog index — lists published posts. */
require_once __DIR__ . '/includes/functions.php';
$page = 'blog';
$page_title = 'Blog | ' . SITE_NAME;
$base = base_path();

$posts = [];
try {
    $stmt = db()->query(
        'SELECT slug, title, excerpt, cover_image, author, category, published_at
         FROM blog_posts WHERE published = 1 ORDER BY published_at DESC, created_at DESC'
    );
    $posts = $stmt->fetchAll();
} catch (Throwable $ex) {
    error_log('blog list failed: ' . $ex->getMessage());
}

include __DIR__ . '/includes/header.php';
?>
<section class="gradient-mesh pt-32 pb-16 px-4 sm:px-6 lg:px-8">
  <div class="max-w-4xl mx-auto text-center">
    <div class="inline-block px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium mb-4">The Doable Blog</div>
    <h1 class="text-premium-heading text-4xl md:text-5xl font-extrabold mb-4">Ideas to grow your <span class="gradient-text">business</span></h1>
    <p class="text-lg text-gray-600">Practical tips on scheduling, retention, marketing, and running a thriving class-based business.</p>
  </div>
</section>

<section class="py-16 px-4 sm:px-6 lg:px-8 bg-white">
  <div class="max-w-6xl mx-auto">
    <?php if (!$posts): ?>
      <p class="text-center text-gray-500">No posts published yet. Check back soon!</p>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($posts as $p): ?>
        <a href="<?= $base ?>/blog-post.php?slug=<?= urlencode($p['slug']) ?>" class="card-premium overflow-hidden flex flex-col group">
          <div class="aspect-video bg-gradient-to-br from-emerald-100 to-teal-100 relative overflow-hidden">
            <?php if (!empty($p['cover_image'])): ?>
              <img src="<?= e($p['cover_image']) ?>" alt="<?= e($p['title']) ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
            <?php else: ?>
              <div class="absolute inset-0 flex items-center justify-center text-5xl">&#9998;</div>
            <?php endif; ?>
          </div>
          <div class="p-6 flex flex-col flex-1">
            <div class="flex items-center gap-2 text-xs text-emerald-600 font-semibold mb-2">
              <span><?= e($p['category']) ?></span>
              <?php if (!empty($p['published_at'])): ?><span class="text-gray-400">&middot; <?= e(date('M j, Y', strtotime($p['published_at']))) ?></span><?php endif; ?>
            </div>
            <h2 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-emerald-600 transition-colors"><?= e($p['title']) ?></h2>
            <p class="text-gray-600 text-sm leading-relaxed flex-1"><?= e($p['excerpt']) ?></p>
            <span class="mt-4 text-emerald-600 font-semibold text-sm">Read more &rarr;</span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
