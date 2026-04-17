<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "NFA ACTIVE CUSTOMERS REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$today = date('Y-m-d');

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';
if (preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $business_name)) {
    $business_name = '';
} else {
    $business_name = '' . $business_name;
}

$location_name = '';
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$resultsArray = [];
while (!$results->EOF) {
    $resultsArray[] = $results->fields['LOCATION_NAME'];
    $results->MoveNext();
}
$totalResults = count($resultsArray);
$concatenatedResults = "";
foreach ($resultsArray as $key => $result) {
    $concatenatedResults .= $result;
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}

// Get unique service providers for filter
$serviceProviders = [];
$spQuery = $db_account->Execute("SELECT DISTINCT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER 
    FROM DOA_ENROLLMENT_SERVICE_PROVIDER 
    LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID 
    LEFT JOIN $master_database.DOA_USER_ROLES AS DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER
    LEFT JOIN $master_database.DOA_USER_LOCATION AS DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER
    WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IS NOT NULL");
while (!$spQuery->EOF) {
    if ($spQuery->fields['SERVICE_PROVIDER']) {
        $serviceProviders[] = $spQuery->fields['SERVICE_PROVIDER'];
    }
    $spQuery->MoveNext();
}
$serviceProviders = array_unique($serviceProviders);
sort($serviceProviders);

// Get unique last appointment providers for filter
$lastProviders = [];
$lpQuery = $db_account->Execute("SELECT DISTINCT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER 
    FROM DOA_APPOINTMENT_MASTER 
    LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER 
    LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER 
    LEFT JOIN $master_database.DOA_USER_ROLES AS DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER
    LEFT JOIN $master_database.DOA_USER_LOCATION AS DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER
    WHERE PK_APPOINTMENT_STATUS = 2 AND DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USERS.FIRST_NAME IS NOT NULL");
while (!$lpQuery->EOF) {
    if ($lpQuery->fields['SERVICE_PROVIDER']) {
        $lastProviders[] = $lpQuery->fields['SERVICE_PROVIDER'];
    }
    $lpQuery->MoveNext();
}
$lastProviders = array_unique($lastProviders);
sort($lastProviders);
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<!-- Include jQuery UI for DatePicker -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<style>
    .filter-bar {
        background: #f8f9fa;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        border: 1px solid #dee2e6;
    }

    .filter-title {
        font-weight: bold;
        margin-bottom: 10px;
        color: #333;
    }

    .filter-group {
        display: inline-block;
        margin-right: 20px;
        margin-bottom: 10px;
        vertical-align: top;
    }

    .filter-group label {
        margin-right: 8px;
        font-weight: 500;
        font-size: 13px;
        display: inline-block;
        margin-bottom: 5px;
    }

    .filter-group select,
    .filter-group input {
        padding: 5px 10px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 13px;
        min-width: 180px;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
    }

    .btn-clear {
        padding: 5px 15px;
        background-color: #6c757d;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        margin-top: 22px;
    }

    .btn-clear:hover {
        background-color: #5a6268;
    }

    .filter-count {
        font-size: 12px;
        color: #28a745;
        margin-left: 10px;
        font-weight: normal;
    }

    .no-records {
        text-align: center;
        padding: 20px;
        font-style: italic;
        color: #999;
    }

    .date-range-group {
        display: inline-block;
        margin-right: 20px;
        margin-bottom: 10px;
        vertical-align: top;
    }

    .date-range-group label {
        margin-right: 8px;
        font-weight: 500;
        font-size: 13px;
        display: block;
        margin-bottom: 5px;
    }

    .date-range-group input {
        width: 140px;
        padding: 5px 10px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 13px;
    }

    .ui-datepicker {
        font-size: 12px;
        z-index: 9999 !important;
    }

    .date-separator {
        margin: 0 5px;
        font-size: 13px;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">

        <div class="page-wrapper" style="padding-top: 0px !important;">

            <div class="container-fluid body_content" style="margin-top: 0px;">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                                <li class="breadcrumb-item active"><a href="customer_summary_report.php"><?= $title ?></a></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div>
                                    <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                    <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                </div>

                                <!-- Filter Bar for Last Three Columns -->
                                <div class="filter-bar">
                                    <div class="filter-title">
                                        📊 Filter Report
                                        <span class="filter-count" id="filter-count"></span>
                                    </div>
                                    <div class="filter-group">
                                        <label>Service Provider:</label>
                                        <div>
                                            <select id="filter-service-provider">
                                                <option value="">All Service Providers</option>
                                                <?php foreach ($serviceProviders as $sp): ?>
                                                    <option value="<?= htmlspecialchars($sp) ?>"><?= htmlspecialchars($sp) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="date-range-group">
                                        <label>Last Appointment Date Range:</label>
                                        <div>
                                            <input type="text" id="date-from" placeholder="From Date (MM-DD-YYYY)" autocomplete="off">
                                            <span class="date-separator">to</span>
                                            <input type="text" id="date-to" placeholder="To Date (MM-DD-YYYY)" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="filter-group">
                                        <label>Last Appointment Provider:</label>
                                        <div>
                                            <select id="filter-last-provider">
                                                <option value="">All Providers</option>
                                                <?php foreach ($lastProviders as $lp): ?>
                                                    <option value="<?= htmlspecialchars($lp) ?>"><?= htmlspecialchars($lp) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="filter-group">
                                        <button class="btn-clear" id="clear-filters">Clear Filters</button>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table id="myTable" class="table table-bordered" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="7"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= " (" . $concatenatedResults . ")" ?></th>
                                            </tr>
                                            <tr>
                                                <th style="text-align: center;">Customer Name</th>
                                                <th style="text-align: center;">Enrollment Name / Number</th>
                                                <th style="text-align: center;">Total</th>
                                                <th style="text-align: center;">Session Left</th>
                                                <th style="text-align: center;">Service Provider</th>
                                                <th style="text-align: center;">Last Appointment Date</th>
                                                <th style="text-align: center;">Service Provider in the Last Appointment</th>
                                            </tr>
                                        </thead>
                                        <tbody id="report-body">
                                            <?php
                                            $i = 1;
                                            $displayedCount = 0;

                                            $row = $db_account->Execute("SELECT 
                                                                            DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                                                                            DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE,
                                                                            DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION,
                                                                            CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CUSTOMER_NAME,
                                                                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                                                                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID
                                                                        FROM DOA_ENROLLMENT_SERVICE 
                                                                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER 
                                                                        JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE 
                                                                        JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER 
                                                                        JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                                                        JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER                                                                            
                                                                        WHERE 
                                                                            DOA_ENROLLMENT_MASTER.STATUS = 'A' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                                                            AND DOA_SERVICE_CODE.IS_GROUP = 0
                                                                            AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0
                                                                            AND DOA_SERVICE_MASTER.PK_SERVICE_CLASS != 5 
                                                                        ORDER BY CUSTOMER_NAME");

                                            while (!$row->EOF) {
                                                $appointment = $db_account->Execute("SELECT PK_APPOINTMENT_MASTER FROM DOA_APPOINTMENT_MASTER WHERE DATE > CURDATE() AND PK_APPOINTMENT_STATUS = 1 AND PK_ENROLLMENT_SERVICE = " . $row->fields['PK_ENROLLMENT_SERVICE']);
                                                if ($appointment->RecordCount() == 0) {

                                                    $results = $db_account->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                    $resultsArray = [];
                                                    while (!$results->EOF) {
                                                        $resultsArray[] = $results->fields['SERVICE_PROVIDER'];
                                                        $results->MoveNext();
                                                    }

                                                    $last_data = $db_account->Execute("SELECT DATE, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER WHERE PK_APPOINTMENT_STATUS = 2 AND PK_ENROLLMENT_SERVICE = " . $row->fields['PK_ENROLLMENT_SERVICE'] . " ORDER BY DATE DESC, START_TIME DESC LIMIT 1");

                                                    $NUMBER_OF_SESSION = getSessionCreatedCount($row->fields['PK_ENROLLMENT_SERVICE']);
                                                    if ($row->fields['NUMBER_OF_SESSION'] > $NUMBER_OF_SESSION) {
                                                        $serviceProvider = (isset($resultsArray[0]) && $resultsArray[0]) ? $resultsArray[0] : '';
                                                        $lastDate = isset($last_data->fields['DATE']) ? date('m-d-Y', strtotime($last_data->fields['DATE'])) : '';
                                                        $lastProvider = isset($last_data->fields['SERVICE_PROVIDER']) ? $last_data->fields['SERVICE_PROVIDER'] : '';

                                                        $displayedCount++;
                                            ?>
                                                        <tr>
                                                            <td style="text-align: center;"><?= htmlspecialchars($row->fields['CUSTOMER_NAME']) ?></td>
                                                            <td style="text-align: center;"><?= htmlspecialchars($row->fields['ENROLLMENT_NAME'] . " / " . $row->fields['ENROLLMENT_ID']) ?></td>
                                                            <td style="text-align: center;"><?= $row->fields['NUMBER_OF_SESSION'] ?></td>
                                                            <td style="text-align: center;"><?= $row->fields['NUMBER_OF_SESSION'] - $NUMBER_OF_SESSION ?></td>
                                                            <td style="text-align: center;" class="service-provider-col"><?= htmlspecialchars($serviceProvider) ?></td>
                                                            <td style="text-align: center;" class="last-date-col" data-date="<?= $lastDate ?>"><?= htmlspecialchars($lastDate) ?></td>
                                                            <td style="text-align: center;" class="last-provider-col"><?= htmlspecialchars($lastProvider) ?></td>
                                                        </tr>
                                            <?php
                                                    }
                                                }
                                                $row->MoveNext();
                                                $i++;
                                            }

                                            if ($displayedCount == 0) {
                                                echo '<tr><td colspan="7" style="text-align: center;">No records found</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {

            // Initialize datepickers
            $("#date-from, #date-to").datepicker({
                dateFormat: "mm-dd-yy",
                changeMonth: true,
                changeYear: true,
                yearRange: "-5:+1",
                onSelect: function(selectedDate) {
                    // Apply filters when date is selected
                    applyFilters();
                }
            });

            // Function to parse date string (MM-DD-YYYY) to Date object
            function parseDate(dateStr) {
                if (!dateStr) return null;
                var parts = dateStr.split('-');
                if (parts.length === 3) {
                    return new Date(parts[2], parts[0] - 1, parts[1]);
                }
                return null;
            }

            function updateFilterCount() {
                var visibleCount = $('#report-body tr:visible').length;
                var totalCount = $('#report-body tr').length;

                // Check if there's a "no records" row
                if ($('#report-body tr:first td:first').text() === 'No records found') {
                    $('#filter-count').text('');
                    return;
                }

                if (visibleCount > 0) {
                    $('#filter-count').text(visibleCount + ' records shown out of ' + totalCount);
                } else if (visibleCount === 0 && totalCount > 0) {
                    $('#filter-count').text('No matching records found');
                } else {
                    $('#filter-count').text('');
                }
            }

            function applyFilters() {
                var serviceProvider = $('#filter-service-provider').val();
                var dateFrom = $('#date-from').val();
                var dateTo = $('#date-to').val();
                var lastProvider = $('#filter-last-provider').val();

                var fromDate = parseDate(dateFrom);
                var toDate = parseDate(dateTo);

                var visibleCount = 0;

                $('#report-body tr').each(function() {
                    var $row = $(this);
                    var show = true;

                    // Check if it's the "no records" row
                    if ($row.find('td:first').text() === 'No records found') {
                        return;
                    }

                    // Filter by Service Provider (5th column - index 4)
                    if (show && serviceProvider !== "") {
                        var spText = $row.find('td:eq(4)').text().trim();
                        if (spText !== serviceProvider) {
                            show = false;
                        }
                    }

                    // Filter by Last Appointment Date Range (6th column - index 5)
                    if (show && (dateFrom !== "" || dateTo !== "")) {
                        var dateText = $row.find('td:eq(5)').text().trim();
                        var rowDate = parseDate(dateText);

                        if (rowDate) {
                            if (fromDate && rowDate < fromDate) {
                                show = false;
                            }
                            if (toDate && rowDate > toDate) {
                                show = false;
                            }
                        } else if (dateText !== "") {
                            // If date is empty or invalid, only show if both filters are empty
                            if (dateFrom !== "" || dateTo !== "") {
                                show = false;
                            }
                        }
                    }

                    // Filter by Last Appointment Provider (7th column - index 6)
                    if (show && lastProvider !== "") {
                        var lpText = $row.find('td:eq(6)').text().trim();
                        if (lpText !== lastProvider) {
                            show = false;
                        }
                    }

                    if (show) {
                        $row.show();
                        visibleCount++;
                    } else {
                        $row.hide();
                    }
                });

                // Show/hide the "no records" message if needed
                if (visibleCount === 0 && $('#report-body tr:first td:first').text() !== 'No records found') {
                    if ($('#report-body tr.no-records-row').length === 0) {
                        $('#report-body').append('<tr class="no-records-row"><td colspan="7" style="text-align: center; padding: 20px;">No records match your filters</td></tr>');
                    }
                } else {
                    $('.no-records-row').remove();
                }

                updateFilterCount();
            }

            // Bind filter events
            $('#filter-service-provider').on('change', function() {
                applyFilters();
            });

            $('#filter-last-provider').on('change', function() {
                applyFilters();
            });

            // Clear filters
            $('#clear-filters').on('click', function() {
                $('#filter-service-provider').val('');
                $('#date-from').val('');
                $('#date-to').val('');
                $('#filter-last-provider').val('');
                applyFilters();
            });

            // Initialize
            setTimeout(function() {
                applyFilters();
            }, 100);
        });
    </script>

    <?php require_once('../includes/footer.php'); ?>
</body>

</html>