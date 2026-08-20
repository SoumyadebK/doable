<?php
/**
 * Shared site header: <head>, styles, sticky navigation.
 *
 * Set these BEFORE including this file (all optional):
 *   $page       - current page slug ('home','blog','privacy','terms') for active states
 *   $page_title - <title> text
 *   $on_home    - true when nav anchor links should be in-page (#features);
 *                 false makes them link back to the homepage (/#features)
 */
require_once __DIR__ . '/functions.php';

$content    = get_content();
$page       = $page       ?? '';
$on_home    = $on_home    ?? ($page === 'home');
$page_title = $page_title ?? (SITE_NAME . ' — Run Your Business Effortlessly');
$base       = base_path();
[$enrollUrl, $enrollExternal] = enroll_link();

/** Build an anchor link that works from any page. */
function nav_anchor(bool $onHome, string $base, string $anchor): string {
    return $onHome ? '#' . $anchor : $base . '/#' . $anchor;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <meta name="description" content="<?= e($content['general']['footerTagline']) ?>">
    <link rel="icon" href="<?= $base ?>/assets/images/doable-logo.png">

    <!-- Tailwind (Play CDN). For production your programmer can compile Tailwind
         to a static CSS file and remove this line — see README.md. -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              emerald: { 50:'#ecfdf5',100:'#d1fae5',200:'#a7f3d0',300:'#6ee7b7',400:'#34d399',500:'#10b981',600:'#059669',700:'#047857',800:'#065f46',900:'#064e3b' }
            },
            fontFamily: { sans: ['Inter','system-ui','-apple-system','Segoe UI','Roboto','sans-serif'] }
          }
        }
      }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/styles.css">
</head>
<body class="antialiased text-gray-900 bg-white">

<nav id="site-nav" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16 md:h-20">
      <a href="<?= $base ?>/index.php" class="flex items-center gap-2">
        <img src="<?= $base ?>/assets/images/doable-logo.png" alt="<?= e(SITE_NAME) ?> logo" class="h-9 md:h-10 w-auto">
      </a>

      <div class="hidden md:flex items-center gap-8">
        <a href="<?= nav_anchor($on_home,$base,'features') ?>" class="text-sm font-medium text-gray-700 hover:text-emerald-600 transition-colors">Features</a>
        <a href="<?= nav_anchor($on_home,$base,'industries') ?>" class="text-sm font-medium text-gray-700 hover:text-emerald-600 transition-colors">Industries</a>
        <a href="<?= nav_anchor($on_home,$base,'pricing') ?>" class="text-sm font-medium text-gray-700 hover:text-emerald-600 transition-colors">Pricing</a>
        <a href="<?= $base ?>/blog.php" class="text-sm font-medium <?= $page==='blog' ? 'text-emerald-600' : 'text-gray-700' ?> hover:text-emerald-600 transition-colors">Blog</a>
        <a href="<?= e($enrollUrl) ?>"<?= $enrollExternal ? ' target="_blank" rel="noopener"' : '' ?> class="text-sm font-medium text-gray-700 hover:text-emerald-600 transition-colors">Enroll</a>
        <a href="<?= nav_anchor($on_home,$base,'contact') ?>" class="btn-premium text-sm">Start Free Trial</a>
      </div>

      <button id="menu-toggle" class="md:hidden inline-flex items-center justify-center p-2 rounded-lg text-gray-700 hover:bg-gray-100" aria-label="Open menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>
    </div>
  </div>

  <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-100 shadow-lg">
    <div class="px-4 py-4 space-y-2">
      <a href="<?= nav_anchor($on_home,$base,'features') ?>" class="block py-2 text-gray-700 font-medium">Features</a>
      <a href="<?= nav_anchor($on_home,$base,'industries') ?>" class="block py-2 text-gray-700 font-medium">Industries</a>
      <a href="<?= nav_anchor($on_home,$base,'pricing') ?>" class="block py-2 text-gray-700 font-medium">Pricing</a>
      <a href="<?= $base ?>/blog.php" class="block py-2 text-gray-700 font-medium">Blog</a>
      <a href="<?= e($enrollUrl) ?>"<?= $enrollExternal ? ' target="_blank" rel="noopener"' : '' ?> class="block py-2 text-gray-700 font-medium">Enroll</a>
      <a href="<?= nav_anchor($on_home,$base,'contact') ?>" class="block btn-premium text-center mt-2">Start Free Trial</a>
    </div>
  </div>
</nav>

<main>
