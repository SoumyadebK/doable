<?php
require_once('../../global/config.php');

$results_per_page = 100;

if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = " AND DOA_USERS.FIRST_NAME LIKE '%".$search_text."%' OR DOA_USERS.EMAIL_ID LIKE '%".$search_text."%' OR DOA_USERS.PHONE LIKE '%".$search_text."%'";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db->Execute("SELECT count(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TOTAL_RECORDS FROM `DOA_ENROLLMENT_MASTER` INNER JOIN DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER INNER JOIN DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION LEFT JOIN DOA_ENROLLMENT_BALANCE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_GET[master_id]' AND DOA_ENROLLMENT_MASTER.PK_ACCOUNT_MASTER=".$_SESSION['PK_ACCOUNT_MASTER'].$search);
$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;
?>

<table  class="table table-striped border" data-page-length='50'>
    <thead>
    <tr>
        <th>No</th>
        <th>Enrollment Id</th>
        <th>Customer</th>
        <th>Email ID</th>
        <th>Phone</th>
        <th>Location</th>
        <th>Actions</th>
        <th>Status</th>
        <th>Cancel</th>
    </tr>
    </thead>

    <tbody>
    <?php
    $i=$page_first_result+1;
    $row = $db->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ACTIVE, DOA_ENROLLMENT_MASTER.STATUS, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_LOCATION.LOCATION_NAME, DOA_ENROLLMENT_BALANCE.TOTAL_BALANCE_PAID, DOA_ENROLLMENT_BALANCE.TOTAL_BALANCE_USED FROM `DOA_ENROLLMENT_MASTER` INNER JOIN DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER INNER JOIN DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION LEFT JOIN DOA_ENROLLMENT_BALANCE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_GET[master_id]' AND DOA_ENROLLMENT_MASTER.PK_ACCOUNT_MASTER=".$_SESSION['PK_ACCOUNT_MASTER'].$search." LIMIT " . $page_first_result . ',' . $results_per_page);
    while (!$row->EOF) {
        $total_credit_balance = ($row->fields['TOTAL_BALANCE_PAID'])?($row->fields['TOTAL_BALANCE_PAID']-$row->fields['TOTAL_BALANCE_USED']):0; ?>
        <tr>
            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$i;?></td>
            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['ENROLLMENT_ID']?></td>
            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['FIRST_NAME']." ".$row->fields['LAST_NAME']?></td>
            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['EMAIL_ID']?></td>
            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['PHONE']?></td>
            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['LOCATION_NAME']?></td>
            <td>
                <a href="enrollment.php?id=<?=$row->fields['PK_ENROLLMENT_MASTER']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <?php if($row->fields['ACTIVE']==1){ ?>
                    <span class="active-box-green"></span>
                <?php } else{ ?>
                    <span class="active-box-red"></span>
                <?php } ?>
            </td>
            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);">
                <?php if ($row->fields['STATUS']=='A') { ?>
                    <span class="status-box" style="background-color: green;">ACTIVE</span>
                <?php } else { ?>
                    <span class="status-box" style="background-color: red;">CANCELLED</span>
                <?php } ?>
            </td>
            <td>
                <?php if ($row->fields['STATUS']=='A') { ?>
                    <a href="javascript:;" onclick="cancelAppointment(<?=$row->fields['PK_ENROLLMENT_MASTER']?>, <?=$row->fields['PK_USER_MASTER']?>, <?=$total_credit_balance?>)"><img src="../assets/images/cancel.png" title="Cancel" style="block-size: 30px; padding-top:5px"></a>
                <?php } else { ?>
                    <a href="all_enrollments.php?id=<?=$row->fields['PK_ENROLLMENT_MASTER']?>&status=active">Active Enrollment</a>
                <?php } ?>
            </td>
        </tr>
        <?php $row->MoveNext();
        $i++; } ?>
    </tbody>
</table>

<div class="center">
    <div class="pagination outer">
        <ul>
            <?php if ($page > 1) { ?>
                <li><a href="javascript:;" onclick="showEnrollmentList(<?=($page-1)?>)">&laquo;</a></li>
            <?php }
            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                echo '<li><a class="'.(($page_count==$page)?"active":"").'" href="javascript:;" onclick="showEnrollmentList('.$page_count.')">' . $page_count . ' </a></li>';
            }
            if ($page < $number_of_page) { ?>
                <li><a href="javascript:;" onclick="showEnrollmentList(<?=($page+1)?>)">&raquo;</a></li>
            <?php } ?>
        </ul>
    </div>
</div>
