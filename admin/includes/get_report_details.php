<?php
require_once('../../global/config.php');

$REPORT_TYPE = $_POST['REPORT_TYPE'];

if ($REPORT_TYPE == 'royalty_service_report') {
    $report_type = 'royalty';
} elseif ($REPORT_TYPE == 'summary_of_studio_business_report') {
    $report_type = 'studio_business';
} elseif ($REPORT_TYPE == 'staff_performance_report') {
    $report_type = 'staff_performance';
} else {
    die();
}

$access_token = getAccessToken();
$authorization = "Authorization: Bearer " . $access_token;

$url = constant('ami_api_url') . '/api/v1/reports';
$data = [
    'type' => $report_type
];
$post_data = callArturMurrayApiGet($url, $data, $authorization);
$return_data = json_decode($post_data, true);
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
    </tbody>
</table>