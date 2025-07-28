<?php require_once("../global/config.php");

$postData = array();

if (!empty($_POST)) {
    $postData = $_POST;
} else {
    $rawInput = file_get_contents("php://input");
    $postData = json_decode($rawInput, true);
}

if (!empty($postData)) {
    if (empty($postData['LOCATION_ID']) || $postData['LOCATION_ID'] == '') {
        $location_data = $db->Execute("SELECT PK_LOCATION FROM DOA_LOCATION WHERE LOCATION_NAME LIKE '%" . $postData['LOCATION_NAME'] . "%'");
        if ($location_data->RecordCount() > 0) {
            $LEADS_DATA['PK_LOCATION'] = $location_data->fields['PK_LOCATION'];
        }
    } else {
        $LEADS_DATA['PK_LOCATION'] = $postData['LOCATION_ID'];
    }
    $LEADS_DATA['FIRST_NAME'] = $postData['FIRST_NAME'];
    $LEADS_DATA['LAST_NAME'] = $postData['LAST_NAME'];
    $LEADS_DATA['PHONE'] = $postData['PHONE'];
    $LEADS_DATA['EMAIL_ID'] = $postData['EMAIL_ID'];
    $LEADS_DATA['DESCRIPTION'] = $postData['DESCRIPTION'];
    $LEADS_DATA['OPPORTUNITY_SOURCE'] = $postData['OPPORTUNITY_SOURCE'];
    $LEADS_DATA['PK_LEAD_STATUS'] = 1;
    if (empty($_GET['id'])) {
        $LEADS_DATA['ACTIVE'] = 1;
        $LEADS_DATA['CREATED_BY']  = 0;
        $LEADS_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_LEADS', $LEADS_DATA, 'insert');
    } else {
        $LEADS_DATA['ACTIVE'] = $postData['ACTIVE'];
        $LEADS_DATA['EDITED_BY'] = 0;
        $LEADS_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_LEADS', $LEADS_DATA, 'update', " PK_LEADS =  '$_GET[id]'");
    }
    $return_data['success'] = 1;
    $return_data['message'] = 'Lead created successfully';
    echo json_encode($return_data);
    exit;
} else {
    $return_data['success'] = 0;
    $return_data['message'] = 'No data provided';
    echo json_encode($return_data);
    exit;
}
