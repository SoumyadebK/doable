<?php
require_once('../../global/config.php');

$results_per_page = 100;

$START_DATE = ' ';
$END_DATE = ' ';
if (isset($_GET['START_DATE']) && $_GET['START_DATE'] != '') {
    $START_DATE = " AND DOA_APPOINTMENT_MASTER.DATE > '$_GET[START_DATE]'";
}
if (isset($_GET['END_DATE']) && $_GET['END_DATE'] != '') {
    $END_DATE = " AND DOA_APPOINTMENT_MASTER.DATE < '$_GET[END_DATE]'";
}

$search_text = '';
$search = $START_DATE.$END_DATE. ' ';
if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = $START_DATE.$END_DATE." AND DOA_ENROLLMENT_MASTER.ENROLLMENT_ID LIKE '%".$search_text."%' OR CUSTOMER.FIRST_NAME LIKE '%".$search_text."%'";
}

if (isset($_GET['master_id']) && $_GET['master_id'] != '') {
    $PK_USER_MASTER = $_GET['master_id'];
    $query = $db->Execute("SELECT DISTINCT($account_database.DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER), count($account_database.DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS TOTAL_RECORDS FROM $account_database.DOA_APPOINTMENT_MASTER LEFT JOIN $account_database.DOA_SERVICE_MASTER ON $account_database.DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = $account_database.DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER_MASTER = $account_database.DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN $master_database.DOA_USERS AS CUSTOMER ON $master_database.DOA_USER_MASTER.PK_USER = $master_database.CUSTOMER.PK_USER LEFT JOIN $master_database.DOA_USER_LOCATION ON $master_database.CUSTOMER.PK_USER = $master_database.DOA_USER_LOCATION.PK_USER LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON $account_database.DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = $master_database.SERVICE_PROVIDER.PK_USER LEFT JOIN $account_database.DOA_SERVICE_CODE ON $account_database.DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = $account_database.DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER ON $account_database.DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = $account_database.DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND $account_database.DOA_APPOINTMENT_MASTER.STATUS = 'A' AND $account_database.DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND $account_database.DOA_APPOINTMENT_MASTER.CUSTOMER_ID='$PK_USER_MASTER' AND $account_database.DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search);
} else {
    $query = $db->Execute("SELECT DISTINCT($account_database.DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER), count($account_database.DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS TOTAL_RECORDS FROM $account_database.DOA_APPOINTMENT_MASTER LEFT JOIN $account_database.DOA_SERVICE_MASTER ON $account_database.DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = $account_database.DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER_MASTER = $account_database.DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN $master_database.DOA_USERS AS CUSTOMER ON $master_database.DOA_USER_MASTER.PK_USER = $master_database.CUSTOMER.PK_USER LEFT JOIN $master_database.DOA_USER_LOCATION ON $master_database.CUSTOMER.PK_USER = $master_database.DOA_USER_LOCATION.PK_USER LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON $account_database.DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = $master_database.SERVICE_PROVIDER.PK_USER LEFT JOIN $account_database.DOA_SERVICE_CODE ON $account_database.DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = $account_database.DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER ON $account_database.DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = $account_database.DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND $account_database.DOA_APPOINTMENT_MASTER.STATUS = 'A' AND $account_database.DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND $account_database.DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search);
}

$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;
?>
<table id="myTable" class="table table-striped border" data-page-length='50'>
    <thead>
    <tr>
        <th>No</th>
        <th>Customer</th>
        <th>Enrollment ID</th>
        <th><?=$service_provider_title?></th>
        <th>Day</th>
        <th>Date</th>
        <th>Time</th>
        <th>Paid</th>
        <th style="text-align: center;">Completed</th>
        <th>Actions</th>
    </tr>
    </thead>

    <tbody >
        <?php
        $i=$page_first_result+1;
        if (isset($_GET['master_id']) && $_GET['master_id'] != '') {
            $PK_USER_MASTER = $_GET['master_id'];
            $appointment_data = $db->Execute("SELECT DISTINCT $account_database.DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, $account_database.DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, $account_database.DOA_APPOINTMENT_MASTER.DATE, $account_database.DOA_APPOINTMENT_MASTER.START_TIME, $account_database.DOA_APPOINTMENT_MASTER.END_TIME, $account_database.DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS, $account_database.DOA_APPOINTMENT_MASTER.IS_PAID, $account_database.DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, CONCAT($master_database.CUSTOMER.FIRST_NAME, ' ', $master_database.CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT($master_database.SERVICE_PROVIDER.FIRST_NAME, ' ', $master_database.SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, $account_database.DOA_SERVICE_MASTER.SERVICE_NAME, $account_database.DOA_SERVICE_CODE.SERVICE_CODE, $account_database.DOA_APPOINTMENT_MASTER.ACTIVE FROM $account_database.DOA_APPOINTMENT_MASTER LEFT JOIN $account_database.DOA_SERVICE_MASTER ON $account_database.DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = $account_database.DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER_MASTER = $account_database.DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN $master_database.DOA_USERS AS CUSTOMER ON $master_database.DOA_USER_MASTER.PK_USER = $master_database.CUSTOMER.PK_USER LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON $account_database.DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = $master_database.SERVICE_PROVIDER.PK_USER LEFT JOIN $account_database.DOA_SERVICE_CODE ON $account_database.DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = $account_database.DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER ON $account_database.DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = $account_database.DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND $account_database.DOA_APPOINTMENT_MASTER.STATUS = 'A' AND $account_database.DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND $account_database.DOA_APPOINTMENT_MASTER.CUSTOMER_ID='$PK_USER_MASTER' AND $account_database.DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search." ORDER BY DATE LIMIT " . $page_first_result . ',' . $results_per_page);
        } else {
            $appointment_data = $db->Execute("SELECT DISTINCT $account_database.DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, $account_database.DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, $account_database.DOA_APPOINTMENT_MASTER.DATE, $account_database.DOA_APPOINTMENT_MASTER.START_TIME, $account_database.DOA_APPOINTMENT_MASTER.END_TIME, $account_database.DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS, $account_database.DOA_APPOINTMENT_MASTER.IS_PAID, $account_database.DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, CONCAT($master_database.CUSTOMER.FIRST_NAME, ' ', $master_database.CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT($master_database.SERVICE_PROVIDER.FIRST_NAME, ' ', $master_database.SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, $account_database.DOA_SERVICE_MASTER.SERVICE_NAME, $account_database.DOA_SERVICE_CODE.SERVICE_CODE, $account_database.DOA_APPOINTMENT_MASTER.ACTIVE FROM $account_database.DOA_APPOINTMENT_MASTER LEFT JOIN $account_database.DOA_SERVICE_MASTER ON $account_database.DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = $account_database.DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER_MASTER = $account_database.DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN $master_database.DOA_USERS AS CUSTOMER ON $master_database.DOA_USER_MASTER.PK_USER = $master_database.CUSTOMER.PK_USER LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON $account_database.DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = $master_database.SERVICE_PROVIDER.PK_USER LEFT JOIN $account_database.DOA_SERVICE_CODE ON $account_database.DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = $account_database.DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER ON $account_database.DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = $account_database.DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND $account_database.DOA_APPOINTMENT_MASTER.STATUS = 'A' AND $account_database.DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND $account_database.DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$search." ORDER BY DATE LIMIT " . $page_first_result . ',' . $results_per_page);
        }

        while (!$appointment_data->EOF) { ?>
        <tr>
            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$i;?></td>
            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['CUSTOMER_NAME']?></td>
            <? if (!empty($appointment_data->fields['ENROLLMENT_ID'])) { ?>
                <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['ENROLLMENT_ID']." || ".$appointment_data->fields['SERVICE_NAME']." || ".$appointment_data->fields['SERVICE_CODE']?></td>
            <? } else { ?>
                <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_NAME']." || ".$appointment_data->fields['SERVICE_CODE']?></td>
            <? } ?>
            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_PROVIDER_NAME']?></td>
            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('l', strtotime($appointment_data->fields['DATE']))?></td>
            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('m/d/Y', strtotime($appointment_data->fields['DATE']))?></td>
            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=($appointment_data->fields['IS_PAID'] == 0)?'Unpaid':'Paid'?></td>
            <td style="text-align: center;">
                <?php if ($appointment_data->fields['PK_APPOINTMENT_STATUS'] == 2){ ?>
                    <i class="fa fa-check-circle" style="font-size:25px;color:#35e235;"></i>
                <?php } else { ?>
                    <a href="all_schedules.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>&action=complete" onclick='javascript:confirmComplete($(this));return false;'><i class="fa fa-check-circle" style="font-size:25px;color:#a9b7a9;"></i></a>
                <?php } ?>
            </td>
            <td>
                <a href="add_schedule.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <a href="copy_schedule.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>"><i class="fa fa-copy"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            </td>
        </tr>
        <?php $appointment_data->MoveNext();
        $i++; } ?>
    </tbody>
</table>

<div class="center">
    <div class="pagination outer">
        <ul>
            <?php if ($page > 1) { ?>
                <li><a href="javascript:;" onclick="showListView(1)">&laquo;</a></li>
                <li><a href="javascript:;" onclick="showListView(<?=($page-1)?>)">&lsaquo;</a></li>
            <?php }
            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                    echo '<li><a class="'.(($page_count==$page)?"active":"").'" href="javascript:;" onclick="showListView('.$page_count.')">' . $page_count . ' </a></li>';
                } elseif ($page_count == ($number_of_page-1)){
                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                } else {
                    echo '<li><a class="hidden" href="javascript:;" onclick="showListView('.$page_count.')">' . $page_count . ' </a></li>';
                }
            }
            if ($page < $number_of_page) { ?>
                <li><a href="javascript:;" onclick="showListView(<?=($page+1)?>)">&rsaquo;</a></li>
                <li><a href="javascript:;" onclick="showListView(<?=$number_of_page?>)">&raquo;</a></li>
            <?php } ?>
        </ul>
    </div>
</div>
