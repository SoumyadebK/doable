<?php

/**
 * blog-post-structured-data.php
 * ---------------------------------------------------------------------------
 * Extra JSON-LD to add ONLY on blog-post.php (a single article page).
 * It tells Google this page is an article, who wrote it and when — which can
 * earn richer search listings.
 *
 * HOW TO USE: inside blog-post.php, after you have loaded the post into a
 * variable (e.g. $post with keys title, excerpt, slug, author, published_at,
 * EDITED_ON, FEATURED_IMAGE_URL), include this file in the <head>:
 *
 *     <?php $post = ...; include __DIR__ . '/blog-post-structured-data.php'; ?>
 *
 * Adjust the array keys below to match your own column names.
 */

if (!isset($post) || !is_array($post)) {
    return;
}

$base   = 'https://doable.net';
$url    = $base . '/blog-post.php?slug=' . rawurlencode($post['SLUG'] ?? '');
$img    = !empty($post['FEATURED_IMAGE_URL'])
    ? ((strpos($post['FEATURED_IMAGE_URL'], 'http') === 0) ? $post['FEATURED_IMAGE_URL'] : $base . '/' . ltrim($post['FEATURED_IMAGE_URL'], '/'))
    : $base . '/v2/assets/images/doable-logo.png';
$pub    = !empty($post['PUBLISHED_AT']) ? date('c', strtotime($post['PUBLISHED_AT'])) : '';
$mod    = !empty($post['EDITED_ON'])   ? date('c', strtotime($post['EDITED_ON']))   : $pub;
$author = $post['AUTHOR_NAME'] ?? 'DOable';

$data = [
    '@context'         => 'https://schema.org',
    '@type'            => 'BlogPosting',
    'headline'         => $post['TITLE'] ?? '',
    'description'      => $post['EXCERPT'] ?? '',
    'image'            => $img,
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
    'url'              => $url,
    'author'          => ['@type' => 'Organization', 'name' => $author],
    'publisher'       => [
        '@type' => 'Organization',
        'name'  => 'DOable',
        'logo'  => ['@type' => 'ImageObject', 'url' => $base . '/v2/assets/images/doable-logo.png'],
    ],
];
if ($pub) {
    $data['datePublished'] = $pub;
}
if ($mod) {
    $data['dateModified']  = $mod;
}
?>
<script type="application/ld+json">
    <?= json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>