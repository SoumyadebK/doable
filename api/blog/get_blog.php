<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
//session_start();
require_once("../../global/config.php");


$PK_BLOG = $_GET['id'] ?? null;

if (!$PK_BLOG) {
    $return_data['status'] = 'error';
    $return_data['message'] = 'Blog ID is required.';
    echo json_encode($return_data);
    exit;
}

$blog_data = $db->Execute("SELECT * FROM DOA_BLOGS WHERE PK_BLOG = '$PK_BLOG'");

if ($blog_data->RecordCount() == 0) {
    $return_data['status'] = 'error';
    $return_data['message'] = 'Blog not found.';
    echo json_encode($return_data);
    exit;
} else {
    $return_data['status'] = 'success';
    $return_data['data'] = $blog_data->fields;
    echo json_encode($return_data);
    exit;
}
