<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
//session_start();
require_once("../../global/config.php");

$PK_BLOG = trim($_GET['id'] ?? '');
// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);

$TITLE = $input['TITLE'];
//$SLUG = $input['SLUG'];
$EXCERPT = $input['EXCERPT'];
$CONTENT = $input['CONTENT'];
$FEATURED_IMAGE_URL = $input['FEATURED_IMAGE_URL'];
$AUTHOR_NAME = $input['AUTHOR_NAME'];
$CATEGORY = $input['CATEGORY'];
$TAGS = $input['TAGS'];
$STATUS = $input['STATUS'];
$SEO_TITLE = $input['SEO_TITLE'];
$SEO_DESCRIPTION = $input['SEO_DESCRIPTION'];
$CANONICAL_URL = $input['CANONICAL_URL'];
$PUBLISHED_AT = $input['PUBLISHED_AT'];
$COMMENTS_ENABLED = $input['COMMENTS_ENABLED'];
$EXTERNAL_SOURCE = $input['EXTERNAL_SOURCE'];
$EXTERNAL_SOURCE_ID = $input['EXTERNAL_SOURCE_ID'];
$CREATED_BY = $input['CREATED_BY'];
$ACTIVE = $input['ACTIVE'];

if (!$PK_BLOG) {
    $return_data['status'] = 'error';
    $return_data['message'] = 'Blog post ID is required.';
    echo json_encode($return_data);
    exit;
}

if ($TITLE) {
    $UPDATE_DATA['TITLE'] = $TITLE;
}

if ($EXCERPT) {
    $UPDATE_DATA['EXCERPT'] = $EXCERPT;
}

if ($CONTENT) {
    $UPDATE_DATA['CONTENT'] = $CONTENT;
}

if ($FEATURED_IMAGE_URL) {
    $UPDATE_DATA['FEATURED_IMAGE_URL'] = $FEATURED_IMAGE_URL;
}

if ($AUTHOR_NAME) {
    $UPDATE_DATA['AUTHOR_NAME'] = $AUTHOR_NAME;
}

if ($CATEGORY) {
    $UPDATE_DATA['CATEGORY'] = $CATEGORY;
}

if ($TAGS) {
    $UPDATE_DATA['TAGS'] = $TAGS;
}

if ($STATUS) {
    $UPDATE_DATA['STATUS'] = $STATUS;
}

if ($SEO_TITLE) {
    $UPDATE_DATA['SEO_TITLE'] = $SEO_TITLE;
}

if ($SEO_DESCRIPTION) {
    $UPDATE_DATA['SEO_DESCRIPTION'] = $SEO_DESCRIPTION;
}

if ($CANONICAL_URL) {
    $UPDATE_DATA['CANONICAL_URL'] = $CANONICAL_URL;
}

if ($PUBLISHED_AT) {
    $UPDATE_DATA['PUBLISHED_AT'] = $PUBLISHED_AT;
}

if ($COMMENTS_ENABLED) {
    $UPDATE_DATA['COMMENTS_ENABLED'] = $COMMENTS_ENABLED;
}

if ($EXTERNAL_SOURCE) {
    $UPDATE_DATA['EXTERNAL_SOURCE'] = $EXTERNAL_SOURCE;
}

if ($EXTERNAL_SOURCE_ID) {
    $UPDATE_DATA['EXTERNAL_SOURCE_ID'] = $EXTERNAL_SOURCE_ID;
}

$UPDATE_DATA['EDITED_ON'] = date('Y-m-d H:i:s');

db_perform('DOA_BLOGS', $UPDATE_DATA, 'update', " PK_BLOG = '$PK_BLOG'");

if ($PK_BLOG) {
    $return_data['status'] = 'success';
    $return_data['message'] = 'Blog post updated successfully.';
    $return_data['data'] = ['PK_BLOG' => $PK_BLOG];
} else {
    $return_data['status'] = 'error';
    $return_data['message'] = 'Failed to update blog post.';
}

echo json_encode($return_data);
exit;
