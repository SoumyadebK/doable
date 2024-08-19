<?php require_once("../global/config.php");

$USER_NAME = trim($_POST['USER_NAME']);
$PASSWORD  = $_POST['PASSWORD'];
$result    = $db->Execute("SELECT DOA_USERS.*, DOA_USER_ROLES.PK_ROLES FROM DOA_USERS INNER JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USERS.USER_NAME = '$USER_NAME' AND DOA_USER_ROLES.PK_ROLES IN (4)");

if($result->RecordCount() == 0){
    $return_data['success'] = 0;
    $return_data['message'] = 'Invalid User ID';
    echo json_encode($return_data); exit;
} else {
    if (password_verify($PASSWORD, $result->fields['PASSWORD'])) {
        if ($result->fields['ACTIVE'] == 0) {
            $return_data['success'] = 0;
            $return_data['message'] = 'Your Account Has Been Blocked. Please Contact The Admin';
            echo json_encode($return_data);
            exit;
        } else {
            $return_data['data']['PK_USER'] = $result->fields['PK_USER'];
            $return_data['data']['PK_ROLES'] = $result->fields['PK_ROLES'];
            $return_data['data']['PK_ACCOUNT'] = $result->fields['PK_ACCOUNT_MASTER'];
            $return_data['data']['FIRST_NAME'] = $result->fields['FIRST_NAME'];
            $return_data['data']['LAST_NAME'] = $result->fields['LAST_NAME'];
            $return_data['data']['EMAIL_ID'] = $result->fields['EMAIL_ID'];
            $return_data['data']['PHONE'] = $result->fields['PHONE'];

            $return_data['success'] = 1;
            $return_data['message'] = 'Success';
            echo json_encode($return_data);
            exit;
        }
    }else{
        $return_data['success'] = 0;
        $return_data['message'] = 'Incorrect Password.';
        echo json_encode($return_data); exit;
    }
}