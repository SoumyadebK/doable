<?php
if (!empty($_GET['cond']) && $_GET['cond'] == 'del'){
    try {
        $db->Execute("DELETE FROM `DOA_USERS` WHERE `PK_USER` = ".$_GET['PK_USER']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
?>