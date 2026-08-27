<?php
require_once('global/config.php');
/** Public blog index — lists published posts. */
require_once __DIR__ . '/v2/includes/functions.php';
$page = 'blog';
$page_title = 'Blog | ' . SITE_NAME;
$base = base_path();

include __DIR__ . '/v2/includes/header.php';
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
        <?php
        $blog_data = $db->Execute("SELECT * FROM DOA_BLOGS WHERE STATUS = 2 AND ACTIVE = 1 ORDER BY PUBLISHED_AT DESC");
        if ($blog_data->RecordCount() == 0): ?>
            <p class="text-center text-gray-500">No posts published yet. Check back soon!</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php while (!$blog_data->EOF): ?>
                    <a href="<?= $base ?>/blog-post.php?slug=<?= urlencode($blog_data->fields['SLUG']) ?>" class="group block">
                        <div class="aspect-video bg-gradient-to-br from-emerald-100 to-teal-100 relative overflow-hidden">
                            <?php if (!empty($blog_data->fields['FEATURED_IMAGE_URL'])): ?>
                                <img src="<?= e($blog_data->fields['FEATURED_IMAGE_URL']) ?>" alt="<?= e($blog_data->fields['TITLE']) ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <?php else: ?>
                                <div class="absolute inset-0 flex items-center justify-center text-5xl">&#9998;</div>
                            <?php endif; ?>
                        </div>
                        <div class="p-6 flex flex-col flex-1">
                            <div class="flex items-center gap-2 text-xs text-emerald-600 font-semibold mb-2">
                                <span><?= e($blog_data->fields['CATEGORY']) ?></span>
                                <?php if (!empty($blog_data->fields['PUBLISHED_AT'])): ?><span class="text-gray-400">&middot; <?= e(date('M j, Y', strtotime($blog_data->fields['PUBLISHED_AT']))) ?></span><?php endif; ?>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-emerald-600 transition-colors"><?= e($blog_data->fields['TITLE']) ?></h2>
                            <p class="text-gray-600 text-sm leading-relaxed flex-1"><?= e($blog_data->fields['EXCERPT']) ?></p>
                            <span class="mt-4 text-emerald-600 font-semibold text-sm">Read more &rarr;</span>
                        </div>
                    </a>
                <?php
                    $blog_data->MoveNext();
                endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/v2/includes/footer.php'; ?>