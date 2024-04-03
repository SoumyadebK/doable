<?php
$ORPHAN_APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
                            DOA_APPOINTMENT_MASTER.GROUP_NAME,
                            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
                            DOA_APPOINTMENT_MASTER.DATE,
                            DOA_APPOINTMENT_MASTER.START_TIME,
                            DOA_APPOINTMENT_MASTER.END_TIME,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            GROUP_CONCAT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) SEPARATOR ',') AS SERVICE_PROVIDER_NAME,
                            GROUP_CONCAT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) SEPARATOR ',') AS CUSTOMER_NAME
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                        
                        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                                
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
                        AND DOA_APPOINTMENT_MASTER.STATUS = 'A'
                        AND DOA_USER_MASTER.PK_USER_MASTER = $PK_USER_MASTER
                        AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'AD-HOC' 
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC";

$query = $db_account->Execute($ORPHAN_APPOINTMENT_QUERY);

$number_of_result =  $query->RecordCount();
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;
$orphan_appointment_data = $db_account->Execute($ORPHAN_APPOINTMENT_QUERY, $page_first_result . ',' . $results_per_page);
if ($orphan_appointment_data->RecordCount() > 0) {
?>
<h5>List of Orphan Appointments</h5>
<table id="myTable" class="table table-striped border" data-page-length='50'>
    <thead>
        <tr>
            <th data-type="number" style="cursor: pointer">No</i></th>
            <th data-type="string" style="cursor: pointer">Customer</th>
            <th data-type="string" style="cursor: pointer">Service</th>
            <th data-type="string" style="cursor: pointer">Service Code</th>
            <th data-type="string" style="cursor: pointer"><?=$service_provider_title?></th>
            <th data-type="string" style="cursor: pointer">Day</th>
            <th data-date data-order style="cursor: pointer">Date</th>
            <th data-type="string" style="cursor: pointer">Time</th>
        </tr>
    </thead>

    <tbody>
    <?php
    $i=$page_first_result+1;
    while (!$orphan_appointment_data->EOF) { ?>
        <tr>
            <td onclick="editpage(<?=$orphan_appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$i;?></td>
            <td onclick="editpage(<?=$orphan_appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$orphan_appointment_data->fields['CUSTOMER_NAME']?></td>
            <td onclick="editpage(<?=$orphan_appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$orphan_appointment_data->fields['SERVICE_NAME']?></td>
            <td onclick="editpage(<?=$orphan_appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$orphan_appointment_data->fields['SERVICE_CODE']?></td>
            <td onclick="editpage(<?=$orphan_appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$orphan_appointment_data->fields['SERVICE_PROVIDER_NAME']?></td>
            <td onclick="editpage(<?=$orphan_appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('l', strtotime($orphan_appointment_data->fields['DATE']))?></td>
            <td onclick="editpage(<?=$orphan_appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('m/d/Y', strtotime($orphan_appointment_data->fields['DATE']))?></td>
            <td onclick="editpage(<?=$orphan_appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('h:i A', strtotime($orphan_appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($orphan_appointment_data->fields['END_TIME']))?></td>
        </tr>
        <?php $orphan_appointment_data->MoveNext();
        $i++; } ?>
    </tbody>
</table>
<?php } ?>