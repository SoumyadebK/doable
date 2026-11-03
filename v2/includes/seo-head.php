<?php

/**
 * seo-head.php  —  Drop-in SEO tags for every page on doable.net
 * ---------------------------------------------------------------------------
 * WHAT IT DOES: outputs the meta description, canonical URL, Open Graph tags
 * (Facebook / LinkedIn / iMessage previews), Twitter Card tags, and JSON-LD
 * structured data (Organization + WebSite + SoftwareApplication).
 *
 * HOW TO USE:
 *   At the very TOP of each page (before any HTML output), set the variables
 *   for that page, then include this file inside the <head>:
 *
 *      <?php
 *        $seo_title = 'DOable — Business Management Software for Studios';
 *        $seo_desc  = 'All-in-one scheduling, billing and marketing software for dance studios, martial arts schools, gyms and class-based businesses.';
 *        $seo_path  = '/';                 // the path of THIS page (see examples below)
 *        // $seo_image = '/v2/assets/images/og-image.jpg'; // optional, overrides default
 *        // $seo_type  = 'website';        // use 'article' on blog posts
 *      ?>
 *      <head>
 *        <?php include __DIR__ . '/seo-head.php'; ?>
 *        ... your existing <link>/<script> tags ...
 *      </head>
 *
 *   PATH EXAMPLES:
 *      Home ........... $seo_path = '/';
 *      Blog list ...... $seo_path = '/blog.php';
 *      A blog post .... $seo_path = '/blog-post.php?slug=' . $slug;
 *      Privacy ........ $seo_path = '/privacy_policy.php';
 *
 * NOTE: This also PRINTS the <title> tag, so REMOVE any existing <title> line
 *       from the page to avoid having two. If you prefer to keep your own
 *       <title>, delete the <title> line near the bottom of this file.
 */

$__base   = 'https://doable.net';
$__title  = isset($seo_title) && $seo_title !== '' ? $seo_title : 'DOable — Run Your Business Effortlessly';
$__desc   = isset($seo_desc)  && $seo_desc  !== '' ? $seo_desc  : 'All-in-one software for private and class-based businesses — scheduling, payments and marketing in one place.';
$__path   = isset($seo_path)  ? $seo_path : '/';
$__canon  = $__base . $__path;
$__image  = isset($seo_image) && $seo_image !== '' ? $seo_image : '/v2/assets/images/og-image.jpg';
$__imgAbs = (strpos($__image, 'http') === 0) ? $__image : $__base . $__image;
$__type   = isset($seo_type) ? $seo_type : 'website';

$e = function ($s) {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};
?>
<title><?= $e($__title) ?></title>
<meta name="description" content="<?= $e($__desc) ?>">
<link rel="canonical" href="<?= $e($__canon) ?>">
<meta name="robots" content="index, follow, max-image-preview:large">

<!-- Open Graph (Facebook, LinkedIn, iMessage) -->
<meta property="og:type" content="<?= $e($__type) ?>">
<meta property="og:site_name" content="DOable">
<meta property="og:title" content="<?= $e($__title) ?>">
<meta property="og:description" content="<?= $e($__desc) ?>">
<meta property="og:url" content="<?= $e($__canon) ?>">
<meta property="og:image" content="<?= $e($__imgAbs) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

<!-- Twitter / X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $e($__title) ?>">
<meta name="twitter:description" content="<?= $e($__desc) ?>">
<meta name="twitter:image" content="<?= $e($__imgAbs) ?>">

<!-- Favicon (fixes the broken icon that currently points to /assets/...) -->
<link rel="icon" href="/v2/assets/images/doable-logo.png">
<link rel="apple-touch-icon" href="/v2/assets/images/doable-logo.png">

<!-- Structured data: Organization + WebSite + SoftwareApplication -->
<script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@graph": [{
        "@type": "Organization",
        "@id": "<?= $__base ?>/#organization",
        "name": "DOable",
        "url": "<?= $__base ?>/",
        "logo": "<?= $__base ?>/v2/assets/images/doable-logo.png",
        "description": "Business management software for private and class-based businesses.",
        "contactPoint": {
          "@type": "ContactPoint",
          "email": "demo@doable.net",
          "contactType": "sales"
        }
      },
      {
        "@type": "WebSite",
        "@id": "<?= $__base ?>/#website",
        "url": "<?= $__base ?>/",
        "name": "DOable",
        "publisher": {
          "@id": "<?= $__base ?>/#organization"
        }
      },
      {
        "@type": "SoftwareApplication",
        "name": "DOable",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "url": "<?= $__base ?>/",
        "description": "All-in-one scheduling, billing, CRM and marketing software for dance studios, martial arts schools, gyms and class-based businesses.",
        "offers": {
          "@type": "Offer",
          "price": "299",
          "priceCurrency": "USD"
        }
      }
    ]
  }
</script>