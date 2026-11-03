<?php
/** Admin dashboard with quick counts. */
require_once __DIR__ . '/auth.php';

function count_rows(string $table): int {
    try { return (int) db()->query("SELECT COUNT(*) AS c FROM `$table`")->fetch()['c']; }
    catch (Throwable $e) { return 0; }
}
$leads   = count_rows('contact_submissions');
$demos   = count_rows('demo_requests');
$posts   = count_rows('blog_posts');
try { $published = (int) db()->query('SELECT COUNT(*) AS c FROM blog_posts WHERE published = 1')->fetch()['c']; }
catch (Throwable $e) { $published = 0; }

$admin_page = 'dashboard'; $admin_title = 'Dashboard';
include __DIR__ . '/layout-header.php';
?>
<h1 class="text-2xl font-bold text-gray-900 mb-1">Dashboard</h1>
<p class="text-gray-500 mb-8">Welcome back. Here&rsquo;s a snapshot of your website.</p>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <div class="text-3xl font-extrabold text-emerald-600"><?= $leads ?></div>
    <div class="text-sm text-gray-500 mt-1">Contact / trial leads</div>
  </div>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <div class="text-3xl font-extrabold text-emerald-600"><?= $demos ?></div>
    <div class="text-sm text-gray-500 mt-1">Demo requests</div>
  </div>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <div class="text-3xl font-extrabold text-emerald-600"><?= $posts ?></div>
    <div class="text-sm text-gray-500 mt-1">Blog posts</div>
  </div>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <div class="text-3xl font-extrabold text-emerald-600"><?= $published ?></div>
    <div class="text-sm text-gray-500 mt-1">Published posts</div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
  <a href="<?= $base ?>/admin/content.php" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow">
    <div class="text-2xl mb-2">&#9998;</div>
    <h3 class="font-bold text-gray-900 mb-1">Edit Page Content</h3>
    <p class="text-sm text-gray-500">Change the text, features, pricing, and everything on your homepage.</p>
  </a>
  <a href="<?= $base ?>/admin/blog.php" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow">
    <div class="text-2xl mb-2">&#128221;</div>
    <h3 class="font-bold text-gray-900 mb-1">Manage Blog</h3>
    <p class="text-sm text-gray-500">Write, edit, publish, or remove blog articles.</p>
  </a>
  <a href="<?= $base ?>/admin/leads.php" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow">
    <div class="text-2xl mb-2">&#128231;</div>
    <h3 class="font-bold text-gray-900 mb-1">View Leads</h3>
    <p class="text-sm text-gray-500">See everyone who submitted the contact or free-trial form.</p>
  </a>
</div>
<?php include __DIR__ . '/layout-footer.php'; ?>
