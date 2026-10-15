<?php
require_once('../../global/config.php');

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
$REPORT_TYPE = $_POST['REPORT_TYPE'];

if ($REPORT_TYPE == 'royalty_service_report') {
    $report_type = 'royalty';
} elseif ($REPORT_TYPE == 'summary_of_studio_business_report') {
    $report_type = 'studio_business';
} elseif ($REPORT_TYPE == 'staff_performance_report') {
    $report_type = 'staff_performance';
} elseif ($REPORT_TYPE == 'miscellaneous_service_summary_report') {
    $report_type = 'miscellaneous';
} else {
    die();
}

$access_token = getAccessToken();
$authorization = "Authorization: Bearer " . $access_token;

if ($report_type != 'miscellaneous') {
    $url = constant('ami_api_url') . '/api/v1/reports';

    $data = [
        'type' => $report_type
    ];
    $post_data = callArturMurrayApiGet($url, $data, $authorization);
    $return_data = json_decode($post_data, true);
}

//pre_r($return_data);

if ($report_type == 'miscellaneous') { ?>
    <table class="table">
        <thead>
            <tr>
                <th style="text-align: center;">Package Name</th>
                <th style="text-align: center;">Exported On</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $report_details = $db_account->Execute("SELECT * FROM `DOA_REPORT_EXPORT_DETAILS` WHERE `REPORT_TYPE` = 'miscellaneous_service_summary_report' AND `PK_LOCATION` = " . $DEFAULT_LOCATION_ID);
            if ($report_details->RecordCount() <= 0) {
                echo "<div class='alert alert-danger'>No history found</div>";
                exit;
            }
            while (!$report_details->EOF) {
                $report_id = $report_details->fields['ID'];
                $url = constant('ami_api_url') . '/api/v1/reports/' . $report_id;

                $data = [
                    'type' => $report_type
                ];
                $post_data = callArturMurrayApiGet($url, $data, $authorization);
                $return_data = json_decode($post_data, true); ?>
                <tr style="text-align: center;">
                    <td>
                        <?= $return_data['event'] ?>
                    </td>
                    <td>
                        <?= ($return_data['revised']) ? date('m/d/Y h:i A', strtotime($return_data['updated_at']))  : date('m/d/Y h:i A', strtotime($return_data['created_at'])) ?>
                    </td>
                </tr>
            <?php $report_details->MoveNext();
            } ?>
        </tbody>
    </table>
<?php } else {
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
            <?php foreach (array_reverse($return_data) as $key => $value) { ?>
                <tr style="text-align: center;">
                    <td>
                        <?= $value['week_number'] ?>
                    </td>
                    <td>
                        <?= $value['week_year'] ?>
                    </td>
                    <td>
                        <?= ($value['revised']) ? date('m/d/Y h:i A', strtotime($value['updated_at']))  : date('m/d/Y h:i A', strtotime($value['created_at'])) ?>
                    </td>
                </tr>
            <?php } ?>

            <!-- <?php foreach (array_reverse($return_data_past_year) as $key => $value) { ?>
                <tr style="text-align: center;">
                    <td>
                        <?= $value['week_number'] ?>
                    </td>
                    <td>
                        <?= $value['week_year'] ?>
                    </td>
                    <td>
                        <?= ($value['revised']) ? date('m/d/Y h:i A', strtotime($value['updated_at']))  : date('m/d/Y h:i A', strtotime($value['created_at'])) ?>
                    </td>
                </tr>
            <?php } ?> -->
        </tbody>
    </table>
<?php
} ?>