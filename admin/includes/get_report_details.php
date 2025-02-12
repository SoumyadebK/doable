<?php
require_once('../../global/config.php');
global $db_account;

$REPORT_TYPE = $_POST['REPORT_TYPE'];

$report_details = $db_account->Execute("SELECT * FROM `DOA_REPORT_EXPORT_DETAILS` WHERE `REPORT_TYPE` = '$REPORT_TYPE' ORDER BY SUBMISSION_DATE DESC");
?>
<table class="table">
    <thead>
        <tr>
            <th style="text-align: center;">Week Number</th>
            <th style="text-align: center;">Year</th>
            <th style="text-align: center;">Exported On</th>
        </tr>
    </thead>
    <tbody>
    <?php while (!$report_details->EOF){ ?>
        <tr style="text-align: center;">
            <td>
                <?=$report_details->fields['WEEK_NUMBER']?>
            </td>
            <td>
                <?=$report_details->fields['YEAR']?>
            </td>
            <td>
                <?=date('m/d/Y H:i A', strtotime($report_details->fields['SUBMISSION_DATE']))?>
            </td>
        </tr>
    <?php $report_details->MoveNext();
        } ?>
    </tbody>
</table>
