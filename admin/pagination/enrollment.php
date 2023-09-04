<?php
require_once('../../global/config.php');
$PK_USER_MASTER='';

$results_per_page = 100;

if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = " AND DOA_USERS.FIRST_NAME LIKE '%".$search_text."%' OR DOA_USERS.EMAIL_ID LIKE '%".$search_text."%' OR DOA_USERS.PHONE LIKE '%".$search_text."%'";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db_account->Execute("SELECT count(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TOTAL_RECORDS FROM `DOA_ENROLLMENT_MASTER` WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_GET[master_id]'".$search);
$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;
?>

<?php $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1"); ?>
<?php
$i=$page_first_result+1;
$row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ACTIVE FROM `DOA_ENROLLMENT_MASTER` WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_GET[master_id]'".$search."ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC"." LIMIT " . $page_first_result . ',' . $results_per_page);
while (!$row->EOF) {
    $serviceMasterData = $db_account->Execute("SELECT DOA_SERVICE_MASTER.SERVICE_NAME FROM DOA_SERVICE_MASTER JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
    $serviceMaster = [];
    while (!$serviceMasterData->EOF) {
        $serviceMaster[] = $serviceMasterData->fields['SERVICE_NAME'];
        $serviceMasterData->MoveNext();
    }



    $used_session_count = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT, PK_SERVICE_MASTER FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']);
    $PK_SERVICE_MASTER = ($used_session_count->RecordCount() > 0) ? $used_session_count->fields['PK_SERVICE_MASTER'] : 0;
    $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']." AND `PK_SERVICE_MASTER` = ".$PK_SERVICE_MASTER);
    if ($total_session->RecordCount() <= 0 || $total_session->fields['TOTAL_SESSION_COUNT'] == '') {
        $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']);
    }
    $total_session_count = ($total_session->RecordCount() > 0) ? $total_session->fields['TOTAL_SESSION_COUNT'] : 0;
    $total_bill_and_paid = $db_account->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID, SUM(BALANCE) AS BALANCE FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
    $price_per_session = ($total_session_count > 0) ? $total_bill_and_paid->fields['TOTAL_PAID']/$total_session_count : 0.00;
    //$enrollment_balance = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
    $total_amount = $db_account->Execute("SELECT SUM(TOTAL_AMOUNT) AS TOTAL_AMOUNT FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
    $total_paid = $total_bill_and_paid->fields['TOTAL_PAID'];
    $balance = $total_bill_and_paid->fields['TOTAL_BILL'] - $total_bill_and_paid->fields['TOTAL_PAID'];
    $total_used = $used_session_count->fields['USED_SESSION_COUNT']*$price_per_session;
    $service_credit = $total_bill_and_paid->fields['TOTAL_PAID']-$total_used;
    ?>
    <div class="row border">
        <div class="col-2" style="text-align: center; margin-top: 1.5%;">
            <a href="enrollment.php?id=<?=$row->fields['PK_ENROLLMENT_MASTER']?>"><?=$row->fields['ENROLLMENT_ID']?></a>
            <p><?=implode(' || ', $serviceMaster)?></p>
        </div>
        <div class="col-8">
            <table id="myTable" class="table table-striped border">
                <thead>
                <tr>
                    <th></th>
                    <th>Enrolled</th>
                    <th>Paid</th>
                    <th>Used</th>
                    <th>Balance</th>
                    <th>Service Credit</th>
                </tr>
                </thead>

                <tbody>
                <?php
                $per_session_cost = $total_bill_and_paid->fields['TOTAL_BILL']/(($total_session_count==0)?1:$total_session_count);
                $total_paid_session_count = ceil($total_bill_and_paid->fields['TOTAL_PAID']/(($per_session_cost==0)?1:$per_session_cost));
                $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                while (!$serviceCodeData->EOF) {
                    $used_session_count = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']." AND PK_SERVICE_CODE = ".$serviceCodeData->fields['PK_SERVICE_CODE']); ?>
                    <tr>
                        <td><?=$serviceCodeData->fields['SERVICE_CODE']?></td>
                        <td><?=$serviceCodeData->fields['NUMBER_OF_SESSION']?></td>
                        <td>
                            <?php
                            if ($total_paid_session_count > $serviceCodeData->fields['NUMBER_OF_SESSION']) {
                                echo $paid_session_count = $serviceCodeData->fields['NUMBER_OF_SESSION'];
                                $total_paid_session_count -= $serviceCodeData->fields['NUMBER_OF_SESSION'];
                            } else {
                                echo $paid_session_count = $total_paid_session_count;
                                $total_paid_session_count = 0;
                            }
                            ?>
                        </td>
                        <td><?=$used_session_count->fields['USED_SESSION_COUNT']?></td>
                        <td><?=$serviceCodeData->fields['NUMBER_OF_SESSION']-$used_session_count->fields['USED_SESSION_COUNT']?></td>
                        <td><?=$paid_session_count-$used_session_count->fields['USED_SESSION_COUNT']?></td>
                    </tr>
                <?php $serviceCodeData->MoveNext();
                } ?>
                <tr>
                    <td>Amount</td>
                    <td><?=$total_bill_and_paid->fields['TOTAL_BILL']?></td>
                    <td><?=$total_bill_and_paid->fields['TOTAL_PAID']?></td>
                    <td><?=$total_used?></td>
                    <td><?=$balance?></td>
                    <td><?=$service_credit?></td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="col-2" style="text-align: center; margin-top: 1.5%;">
            <p>Wallet Balance : $<?=$balance?></p>
        </div>
    </div>

    <!--<div class="" onclick="showEnrollmentPanel()">
        <button type="button" class="btn btn-info d-none d-lg-block m-l-10 text-white">  <?php /*=$row->fields['ENROLLMENT_ID']*/?> </button>
    </div>
    <div class="row" onclick="$(this).next().slideToggle()" style="cursor:pointer; font-size: 15px; *border: 1px solid #ebe5e2; padding: 8px;">
        <div class="col-2"><i class="ti-arrow-circle-right"></i>  Enrolled : <?php /*=$total_bill_and_paid->fields['TOTAL_BILL'];*/?></div>
        <div class="col-2">Paid : <?php /*=$total_bill_and_paid->fields['TOTAL_PAID'];*/?></div>
        <div class="col-2">Used : <?php /*=number_format((float)$total_used, 2, '.', ',');*/?></div>
        <div class="col-2">Balance : <?php /*=$balance*/?></div>
        <div class="col-2" style="color:<?php /*=($service_credit<0)?'red':'black'*/?>;">Service Credit : <?php /*=number_format((float)$service_credit, 2, '.', ',');*/?></div>
        <div class="col-2">Session : <?php /*=$used_session_count->fields['USED_SESSION_COUNT'].'/'.$total_session_count;*/?></div>
    </div>-->

    <?php $row->MoveNext();
    $i++; } ?>

<div class="center">
    <div class="pagination outer">
        <ul>
            <?php if ($page > 1) { ?>
                <li><a href="javascript:;" onclick="showLedgerList(1)">&laquo;</a></li>
                <li><a href="javascript:;" onclick="showLedgerList(<?=($page-1)?>)">&lsaquo;</a></li>
            <?php }
            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                    echo '<li><a class="'.(($page_count==$page)?"active":"").'" href="javascript:;" onclick="showLedgerList('.$page_count.')">' . $page_count . ' </a></li>';
                } elseif ($page_count == ($number_of_page-1)){
                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                } else {
                    echo '<li><a class="hidden" href="javascript:;" onclick="showLedgerList('.$page_count.')">' . $page_count . ' </a></li>';
                }
            }
            if ($page < $number_of_page) { ?>
                <li><a href="javascript:;" onclick="showLedgerList(<?=($page+1)?>)">&rsaquo;</a></li>
                <li><a href="javascript:;" onclick="showLedgerList(<?=$number_of_page?>)">&raquo;</a></li>
            <?php } ?>
        </ul>

        <!--<ul>
            <?php /*if ($page > 1) { */?>
                <li><a href="javascript:;" onclick="showLedgerList(<?php /*=($page-1)*/?>)">&laquo;</a></li>
            <?php /*}
            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                echo '<li><a class="'.(($page_count==$page)?"active":"").'" href="javascript:;" onclick="showLedgerList('.$page_count.')">' . $page_count . ' </a></li>';
            }
            if ($page < $number_of_page) { */?>
                <li><a href="javascript:;" onclick="showLedgerList(<?php /*=($page+1)*/?>)">&raquo;</a></li>
            <?php /*} */?>
        </ul>-->
    </div>
</div>

<script>
    function showEnrollmentPanel() {
        //$('#enrollment_header').text("Add Enrollment");
        openEnrollmentPanel();
    }
</script>

<script>
    // Get the modal
    var enrollment_panel = document.getElementById("enrollmentPanel");

    // Get the <span> element that closes the enrollment_model
    var enrollment_span = document.getElementsByClassName("close_enrollment_panel")[0];

    // When the user clicks the button, open the enrollment_model
    function openEnrollmentPanel() {
        enrollment_panel.style.display = "block";
    }

    // When the user clicks on <appointment_span> (x), close the appointment_model
    enrollment_span.onclick = function() {
        enrollment_panel.style.display = "none";
    }

    // When the user clicks anywhere outside of the appointment_model, close it
    window.onclick = function(event) {
        if (event.target == enrollment_model) {
            enrollment_panel.style.display = "none";
        }
    }

    $(document).keydown(function(e) {
        // ESCAPE key pressed
        if (e.keyCode == 27) {
            enrollment_panel.style.display = "none";
        }
    });
</script>
