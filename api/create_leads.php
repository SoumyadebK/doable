<?php require_once("../global/config.php");

if (!empty($_POST)) {
    if (empty($_POST['LOCATION_ID']) || $_POST['LOCATION_ID'] == '') {
        $location_data = $db->Execute("SELECT PK_LOCATION FROM DOA_LOCATION WHERE LOCATION_NAME LIKE '%" . $_POST['LOCATION_NAME'] . "%'");
        if ($location_data->RecordCount() > 0) {
            $LEADS_DATA['PK_LOCATION'] = $location_data->fields['PK_LOCATION'];
        }
    } else {
        $LEADS_DATA['PK_LOCATION'] = $_POST['LOCATION_ID'];
    }
    $LEADS_DATA['FIRST_NAME'] = $_POST['FIRST_NAME'];
    $LEADS_DATA['LAST_NAME'] = $_POST['LAST_NAME'];
    $LEADS_DATA['PHONE'] = $_POST['PHONE'];
    $LEADS_DATA['EMAIL_ID'] = $_POST['EMAIL_ID'];
    $LEADS_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
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
