<?php
/** Admin chrome (top bar + nav). Expects auth.php already included. Set $admin_page + $admin_title. */
$admin_page  = $admin_page  ?? '';
$admin_title = $admin_title ?? 'Admin';
$base = base_path();
function anav(string $cur, string $id, string $href, string $label, string $base): string {
    $active = $cur === $id ? 'bg-emerald-600 text-white' : 'text-gray-600 hover:bg-emerald-50 hover:text-emerald-700';
    return '<a href="' . $base . $href . '" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors ' . $active . '">' . e($label) . '</a>';
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($admin_title) ?> | <?= e(SITE_NAME) ?> Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/styles.css">
</head>
<body class="min-h-screen bg-gray-50">
  <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
      <div class="flex items-center gap-3">
        <img src="<?= $base ?>/assets/images/doable-logo.png" alt="<?= e(SITE_NAME) ?>" class="h-8">
        <span class="text-gray-400">|</span>
        <span class="font-semibold text-gray-700">Admin</span>
      </div>
      <div class="flex items-center gap-4 text-sm">
        <a href="<?= $base ?>/index.php" target="_blank" class="text-gray-500 hover:text-emerald-600">View site &#8599;</a>
        <span class="text-gray-400">Hi, <?= e($admin_name) ?></span>
        <a href="<?= $base ?>/admin/logout.php" class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 font-medium">Log out</a>
      </div>
    </div>
  </header>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-8">
    <nav class="space-y-1 lg:sticky lg:top-24 self-start">
      <?= anav($admin_page,'dashboard','/admin/index.php','Dashboard',$base) ?>
      <?= anav($admin_page,'content','/admin/content.php','Page Content',$base) ?>
      <?= anav($admin_page,'blog','/admin/blog.php','Blog Posts',$base) ?>
      <?= anav($admin_page,'leads','/admin/leads.php','Leads',$base) ?>
    </nav>
    <main class="min-w-0">
