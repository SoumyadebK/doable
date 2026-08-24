<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
//session_start();
require_once("../../global/config.php");

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);

$TITLE = $input['TITLE'];
$SLUG = $input['SLUG'];
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

if (!$TITLE) {
    $return_data['status'] = 'error';
    $return_data['message'] = 'Title is required.';
    echo json_encode($return_data);
    exit;
}

if (!$SLUG) {
    $return_data['status'] = 'error';
    $return_data['message'] = 'Slug is required.';
    echo json_encode($return_data);
    exit;
} else {
    $SLUG = generateSlug($SLUG);
    $existing_slug = $db->Execute("SELECT * FROM DOA_BLOGS WHERE SLUG = '$SLUG'");
    if ($existing_slug->RecordCount() > 0) {
        $return_data['status'] = 'error';
        $return_data['message'] = 'Slug already exists.';
        echo json_encode($return_data);
        exit;
    }
}

if (!$CONTENT) {
    $return_data['status'] = 'error';
    $return_data['message'] = 'Content is required.';
    echo json_encode($return_data);
    exit;
}

$INSERT_DATA = [
    'TITLE' => $TITLE,
    'SLUG' => generateSlug($SLUG),
    'EXCERPT' => $EXCERPT,
    'CONTENT' => $CONTENT,
    'FEATURED_IMAGE_URL' => $FEATURED_IMAGE_URL,
    'AUTHOR_NAME' => $AUTHOR_NAME,
    'CATEGORY' => $CATEGORY,
    'TAGS' => $TAGS,
    'STATUS' => $STATUS,
    'SEO_TITLE' => $SEO_TITLE,
    'SEO_DESCRIPTION' => $SEO_DESCRIPTION,
    'CANONICAL_URL' => $CANONICAL_URL,
    'PUBLISHED_AT' => $PUBLISHED_AT,
    'COMMENTS_ENABLED' => $COMMENTS_ENABLED,
    'EXTERNAL_SOURCE' => $EXTERNAL_SOURCE,
    'EXTERNAL_SOURCE_ID' => $EXTERNAL_SOURCE_ID,
    'CREATED_BY' => $CREATED_BY,
    'ACTIVE' => 1
];

db_perform('DOA_BLOGS', $INSERT_DATA, 'insert');
$PK_BLOG = $db->insert_ID();

if ($PK_BLOG) {
    $return_data['status'] = 'success';
    $return_data['message'] = 'Blog post created successfully.';
    $return_data['data'] = ['PK_BLOG' => $PK_BLOG];
} else {
    $return_data['status'] = 'error';
    $return_data['message'] = 'Failed to create blog post.';
}

echo json_encode($return_data);
exit;

function generateSlug($title)
{
    $slug = strtolower(trim($title));

    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');

    return $slug;
}
