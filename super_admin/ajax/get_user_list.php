<?php
require_once('../../global/config.php');
$title = "User List";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

$PK_LOCATION = $_POST['PK_LOCATION'];

if (!empty($_POST['cond']) && $_POST['cond'] == 'del'){
    $db->Execute("DELETE FROM `DOA_USERS` WHERE `PK_USER` = ".$_POST['PK_USER']);
    header('location:user_list.php?id='.$PK_LOCATION);
}

$location_name = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE PK_LOCATION = '$PK_LOCATION'");
?>

<table class="table table-striped border">
    <thead>
        <tr>
            <th>No</th>
            <th>Name</th>
            <th>Username</th>
            <th>Roles</th>
            <th>Email Id</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $i=1;
    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_LOCATION ON  DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_ROLES.PK_ROLES IN(2,3,5,6,7,8) AND DOA_USER_LOCATION.PK_LOCATION = '$PK_LOCATION' ORDER BY DOA_USERS.ACTIVE DESC, DOA_USERS.FIRST_NAME ASC");
    while (!$row->EOF) {
        $selected_roles = [];
        if(!empty($row->fields['PK_USER'])) {
            $PK_USER = $row->fields['PK_USER'];
            $selected_roles_row = $db->Execute("SELECT DOA_ROLES.ROLES FROM `DOA_USER_ROLES` LEFT JOIN DOA_ROLES ON DOA_USER_ROLES.PK_ROLES = DOA_ROLES.PK_ROLES WHERE `PK_USER` = '$PK_USER'");
            while (!$selected_roles_row->EOF) {
                $selected_roles[] = $selected_roles_row->fields['ROLES'];
                $selected_roles_row->MoveNext();
            }
        } ?>
        <tr>
            <td onclick="editUser(<?=$row->fields['PK_USER']?>, <?=$_POST['PK_LOCATION']?>);"><?=$i;?></td>
            <td onclick="editUser(<?=$row->fields['PK_USER']?>, <?=$_POST['PK_LOCATION']?>);"><?=$row->fields['NAME']?></td>
            <td onclick="editUser(<?=$row->fields['PK_USER']?>, <?=$_POST['PK_LOCATION']?>);"><?=$row->fields['USER_NAME']?></td>
            <td onclick="editUser(<?=$row->fields['PK_USER']?>, <?=$_POST['PK_LOCATION']?>);"><?=implode(', ', $selected_roles)?></td>
            <td onclick="editUser(<?=$row->fields['PK_USER']?>, <?=$_POST['PK_LOCATION']?>);"><?=$row->fields['EMAIL_ID']?></td>
            <td style="padding: 10px 0px 0px 0px;font-size: 20px;">
                <a href="edit_account_user.php?id=<?=$row->fields['PK_USER']?>&ac_id=<?=$_POST['PK_LOCATION']?>" title="Reset Password" style="color: #03a9f3;"><i class="ti-lock"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;
                <?php if($row->fields['ACTIVE']==1){ ?>
                    <span title="Active" class="active-box-green"></span>
                <?php } else{ ?>
                    <span title="Inactive" class="active-box-red"></span>
                <?php } ?>&nbsp;&nbsp;
                <a href="javascript:;" data-href="account.php?id=<?=$_POST['PK_LOCATION']?>&PK_USER=<?=$row->fields['PK_USER']?>&cond=del" onclick="confirmDeleteUser(this);" title="Delete" style="color: red;"><i class="ti-trash"></i></a>
            </td>
        </tr>
        <?php $row->MoveNext();
        $i++; } ?>
    </tbody>
</table>
<script>

function confirmDeleteUser(anchor) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location = $(anchor).data("href");
        }
    });
}
</script>