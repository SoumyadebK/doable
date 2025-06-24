<?php require_once("../global/config.php");

if (!empty($_POST)) {
    $LEADS_DATA = $_POST;
    $LEADS_DATA['PK_LEAD_STATUS'] = 1;
    if (empty($_GET['id'])) {
        $LEADS_DATA['ACTIVE'] = 1;
        $LEADS_DATA['CREATED_BY']  = 0;
        $LEADS_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_LEADS', $LEADS_DATA, 'insert');
    } else {
        $LEADS_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $LEADS_DATA['EDITED_BY'] = 0;
        $LEADS_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_LEADS', $LEADS_DATA, 'update', " PK_LEADS =  '$_GET[id]'");
    }
    $return_data['success'] = 1;
    $return_data['message'] = 'Success';
    echo json_encode($return_data);
    exit;
}
