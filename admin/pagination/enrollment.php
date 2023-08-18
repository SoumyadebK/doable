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

$query = $db_account->Execute("SELECT count(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TOTAL_RECORDS FROM `DOA_ENROLLMENT_MASTER` INNER JOIN $master_database.DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $master_database.DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS ON $master_database.DOA_USERS.PK_USER = $master_database.DOA_USER_MASTER.PK_USER LEFT JOIN $master_database.DOA_LOCATION ON $master_database.DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION LEFT JOIN DOA_ENROLLMENT_BALANCE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_GET[master_id]' AND DOA_ENROLLMENT_MASTER.PK_ACCOUNT_MASTER=".$_SESSION['PK_ACCOUNT_MASTER'].$search);
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
    $row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ACTIVE, DOA_ENROLLMENT_MASTER.STATUS, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, $master_database.DOA_USERS.FIRST_NAME, $master_database.DOA_USERS.LAST_NAME, $master_database.DOA_USERS.EMAIL_ID, $master_database.DOA_USERS.PHONE, $master_database.DOA_LOCATION.LOCATION_NAME FROM `DOA_ENROLLMENT_MASTER` INNER JOIN $master_database.DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $master_database.DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS ON $master_database.DOA_USERS.PK_USER = $master_database.DOA_USER_MASTER.PK_USER LEFT JOIN $master_database.DOA_LOCATION ON $master_database.DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION WHERE $master_database.DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_GET[master_id]' AND DOA_ENROLLMENT_MASTER.PK_ACCOUNT_MASTER=".$_SESSION['PK_ACCOUNT_MASTER'].$search." ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC LIMIT " . $page_first_result . ',' . $results_per_page);
    while (!$row->EOF) {
        $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.SERVICE_CODE FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
        $serviceCode = [];
        while (!$serviceCodeData->EOF) {
            $serviceCode[] = $serviceCodeData->fields['SERVICE_CODE'];
            $serviceCodeData->MoveNext();
        }
        ?>
        <tr>
            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$i;?></td>
            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['ENROLLMENT_ID']." || ".implode(', ', $serviceCode)?></td>
            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['FIRST_NAME']." ".$row->fields['LAST_NAME']?></td>
            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['EMAIL_ID']?></td>
            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['PHONE']?></td>
            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['LOCATION_NAME']?></td>
            <td>
                <a href="enrollment.php?id=<?=$row->fields['PK_ENROLLMENT_MASTER']?>" target="_blank" title="Edit Enrollment" style="font-size:18px"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <?php if($row->fields['ACTIVE']==1){ ?>
                    <span class="active-box-green"></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <?php } else{ ?>
                    <span class="active-box-red"></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <?php } ?>
            </td>
            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);">
                <?php if ($row->fields['STATUS']=='A') { ?>
                    <i class="fa fa-check-circle" style="font-size:21px;color:#35e235;"></i>
                <?php } else { ?>
                    <span class="fa fa-check-circle" style="font-size:21px;color:#ff0000;"></span>
                <?php } ?>
            </td>
            <td>
                <?php if ($row->fields['STATUS']=='A') { ?>
                    <a href="javascript:;" onclick="cancelAppointment(<?=$row->fields['PK_ENROLLMENT_MASTER']?>, <?=$row->fields['PK_USER_MASTER']?>, 0)"><img src="../assets/images/noun-cancel-button.png" alt="LOGO" style="height: 21px; width: 21px;"></a>
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
                <li><a href="javascript:;" onclick="showEnrollmentList(1)">&laquo;</a></li>
                <li><a href="javascript:;" onclick="showEnrollmentList(<?=($page-1)?>)">&lsaquo;</a></li>
            <?php }
            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                    echo '<li><a class="'.(($page_count==$page)?"active":"").'" href="javascript:;" onclick="showEnrollmentList('.$page_count.')">' . $page_count . ' </a></li>';
                } elseif ($page_count == ($number_of_page-1)){
                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                } else {
                    echo '<li><a class="hidden" href="javascript:;" onclick="showEnrollmentList('.$page_count.')">' . $page_count . ' </a></li>';
                }
            }
            if ($page < $number_of_page) { ?>
                <li><a href="javascript:;" onclick="showEnrollmentList(<?=($page+1)?>)">&rsaquo;</a></li>
                <li><a href="javascript:;" onclick="showEnrollmentList(<?=$number_of_page?>)">&raquo;</a></li>
            <?php } ?>
        </ul>
        <!--<ul>
            <?php /*if ($page > 1) { */?>
                <li><a href="javascript:;" onclick="showEnrollmentList(<?php /*=($page-1)*/?>)">&laquo;</a></li>
            <?php /*}
            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                echo '<li><a class="'.(($page_count==$page)?"active":"").'" href="javascript:;" onclick="showEnrollmentList('.$page_count.')">' . $page_count . ' </a></li>';
            }
            if ($page < $number_of_page) { */?>
                <li><a href="javascript:;" onclick="showEnrollmentList(<?php /*=($page+1)*/?>)">&raquo;</a></li>
            <?php /*} */?>
        </ul>-->
    </div>
</div>
