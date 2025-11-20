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
        $location_data = $db->Execute("SELECT PK_LOCATION, PK_ACCOUNT_MASTER FROM DOA_LOCATION WHERE LOCATION_NAME LIKE '%" . $postData['LOCATION_NAME'] . "%'");
        if ($location_data->RecordCount() > 0) {
            $PK_LOCATION = $location_data->fields['PK_LOCATION'];
            $PK_ACCOUNT_MASTER = $location_data->fields['PK_ACCOUNT_MASTER'];
        }
    } else {
        $PK_LOCATION = $postData['LOCATION_ID'];
        $location_data = $db->Execute("SELECT PK_ACCOUNT_MASTER FROM DOA_LOCATION WHERE PK_LOCATION = '$PK_LOCATION'");
        if ($location_data->RecordCount() > 0) {
            $PK_ACCOUNT_MASTER = $location_data->fields['PK_ACCOUNT_MASTER'];
        }

        $lead_status = ['New' => '#fffbb9', 'Enrolled' => '#96d35f', 'Not Enrolled' => '#ffa57d'];
        $i = 1;
        foreach ($lead_status as $key => $value) {
            $is_exist = $db->Execute("SELECT * FROM DOA_LEAD_STATUS WHERE LEAD_STATUS='" . $key . "' AND PK_ACCOUNT_MASTER='$PK_ACCOUNT_MASTER'");
            if ($is_exist->RecordCount() == 0) {
                $lead_status_data['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                $lead_status_data['LEAD_STATUS'] = $key;
                $lead_status_data['STATUS_COLOR'] = $value;
                $lead_status_data['DISPLAY_ORDER'] = $i;
                $lead_status_data['ACTIVE'] = 1;
                db_perform('DOA_LEAD_STATUS', $lead_status_data, 'insert');
            }
            $i++;
        }
    }
    $lead_status_data = $db->Execute("SELECT PK_LEAD_STATUS FROM DOA_LEAD_STATUS WHERE LEAD_STATUS = 'New' AND PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
    $LEADS_DATA['PK_LOCATION'] = $PK_LOCATION;
    $LEADS_DATA['FIRST_NAME'] = $postData['FIRST_NAME'];
    $LEADS_DATA['LAST_NAME'] = $postData['LAST_NAME'];
    $LEADS_DATA['PHONE'] = $postData['PHONE'];
    $LEADS_DATA['EMAIL_ID'] = $postData['EMAIL_ID'];
    $LEADS_DATA['DESCRIPTION'] = $postData['DESCRIPTION'];
    $LEADS_DATA['OPPORTUNITY_SOURCE'] = $postData['OPPORTUNITY_SOURCE'];
    $LEADS_DATA['PK_LEAD_STATUS'] = $lead_status_data->fields['PK_LEAD_STATUS'];

    if (empty($_GET['id'])) {
        $LEADS_DATA['REMOTE_ADDRESS'] = $_SERVER['REMOTE_ADDR'];
        $LEADS_DATA['IP_ADDRESS'] = getUserIP();
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


function getUserIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        // IP from shared internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // IP passed from proxy
        $ipArray = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipArray[0]); // first IP is the real one
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        // Nginx real IP header
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } else {
        // Default
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
