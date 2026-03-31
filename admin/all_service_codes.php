<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

// Simple fix - convert to array if it's a string
if (!is_array($DEFAULT_LOCATION_ID)) {
    // If it's a comma-separated string like "13, 27"
    if (strpos($DEFAULT_LOCATION_ID, ',') !== false) {
        $DEFAULT_LOCATION_ID = array_map('trim', explode(',', $DEFAULT_LOCATION_ID));
    } else {
        // If it's a single value
        $DEFAULT_LOCATION_ID = !empty($DEFAULT_LOCATION_ID) ? [$DEFAULT_LOCATION_ID] : [];
    }
}

// Get location count
$location_count = count($DEFAULT_LOCATION_ID);
$multiple_locations = ($location_count > 1);

$title = "All Services / Service Codes";

$status_check = empty($_GET['status']) ? 'active' : $_GET['status'];

if ($status_check == 'active') {
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Service Codes page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

// Convert array to string for SQL IN clause
$location_ids_for_sql = implode(',', $DEFAULT_LOCATION_ID);
?>

<!DOCTYPE html>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<html lang="en">
<?php require_once('../includes/header.php'); ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <?php require_once('../includes/setup_menu.php') ?>
            <div class="container-fluid body_content m-0">
                <div class="row page-titles">
                    <div class="col-md-3 align-self-center">
                        <?php if ($status_check == 'inactive') { ?>
                            <h4 class="text-themecolor">Not Active Services / Service Codes</h4>
                        <?php } elseif ($status_check == 'active') { ?>
                            <h4 class="text-themecolor">Active Services / Service Codes</h4>
                        <?php } ?>
                    </div>

                    <?php if ($status_check == 'inactive') { ?>
                        <div class="col-md-3 align-self-center">
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_service_codes.php?status=active'"><i class="fa fa-user"></i> Show Active</button>
                        </div>
                    <?php } elseif ($status_check == 'active') { ?>
                        <div class="col-md-3 align-self-center">
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_service_codes.php?status=inactive'"><i class="fa fa-user-times"></i> Show Not Active</button>
                        </div>
                    <?php } ?>

                    <div class="col-md-6 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="createNewService()">
                                <i class="fa fa-plus-circle"></i> Create New
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row" style="text-align: center;">
                                    <h5 style="font-weight: bold;"><?= $header_text ?></h5>
                                </div>
                                <div class="table-responsive">
                                    <table id="myTable" class="table table-striped border" data-page-length="50">
                                        <thead>
                                            <tr>
                                                <th style="text-align: left">No</th>
                                                <th style="text-align: center">Service Name</th>
                                                <th style="text-align: center">Service Code</th>
                                                <th style="text-align: center">Location Name</th>
                                                <th style="text-align: center">Description</th>
                                                <th style="text-align: center">Upload Documents</th>
                                                <th style="text-align: center">Count on Calendar</th>
                                                <th style="text-align: center">Sort By Order</th>
                                                <th style="text-align: center">Actions</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                            $i = 1;

                                            // Check if there are locations selected
                                            if (!empty($location_ids_for_sql)) {
                                                $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE, DOA_SERVICE_CODE.COUNT_ON_CALENDAR, DOA_SERVICE_CODE.SORT_ORDER, DOA_LOCATION.LOCATION_NAME FROM `DOA_SERVICE_MASTER` LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_SERVICE_MASTER.PK_LOCATION = DOA_LOCATION.PK_LOCATION WHERE DOA_SERVICE_MASTER.PK_LOCATION IN (" . $location_ids_for_sql . ") AND IS_DELETED = 0 AND DOA_SERVICE_MASTER.ACTIVE = '$status' ORDER BY DOA_SERVICE_CODE.SORT_ORDER ASC");

                                                while (!$row->EOF) { ?>
                                                    <tr>
                                                        <td style="text-align: center" onclick="editpage(<?= $row->fields['PK_SERVICE_MASTER'] ?>);"><?= $i; ?></td>
                                                        <td style="text-align: center" onclick="editpage(<?= $row->fields['PK_SERVICE_MASTER'] ?>);"><?= $row->fields['SERVICE_NAME'] ?></td>
                                                        <td style="text-align: center" onclick="editpage(<?= $row->fields['PK_SERVICE_MASTER'] ?>);"><?= $row->fields['SERVICE_CODE'] ?></td>
                                                        <td style="text-align: center" onclick="editpage(<?= $row->fields['PK_SERVICE_MASTER'] ?>);"><?= $row->fields['LOCATION_NAME'] ?></td>
                                                        <td style="text-align: center" onclick="editpage(<?= $row->fields['PK_SERVICE_MASTER'] ?>);"><?= $row->fields['DESCRIPTION'] ?></td>
                                                        <td style="text-align: center" onclick="editpage(<?= $row->fields['PK_SERVICE_MASTER'] ?>);">
                                                            <?php
                                                            $doc_row = $db_account->Execute("SELECT PK_SERVICE_DOCUMENTS FROM `DOA_SERVICE_DOCUMENTS` WHERE PK_SERVICE_MASTER = " . $row->fields['PK_SERVICE_MASTER']);
                                                            $doc_count = $doc_row->RecordCount();
                                                            ?>
                                                            <i class="fas fa-upload"></i> (<?= $doc_count; ?>)
                                                        </td>
                                                        <td style="text-align: center" onclick="changeCountOnCalendar(<?= $row->fields['PK_SERVICE_MASTER'] ?>);">
                                                            <label class="switch">
                                                                <input type="checkbox" <?= ($row->fields['COUNT_ON_CALENDAR'] == 1) ? 'checked' : '' ?>>
                                                                <span class="slider"></span>
                                                            </label>
                                                        </td>
                                                        <td style="text-align: center" onclick="editpage(<?= $row->fields['PK_SERVICE_MASTER'] ?>);"><?= $row->fields['SORT_ORDER'] ?></td>
                                                        <td style="text-align: center">
                                                            <a href="javascript:;" onclick="editpage(<?= $row->fields['PK_SERVICE_MASTER'] ?>);" title="Edit" style="font-size:18px"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                            <a href="javascript:;" onclick="ConfirmDelete(<?= $row->fields['PK_SERVICE_MASTER'] ?>);" title="Delete" style="font-size:18px"><i class="fa fa-trash"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                            <?php if ($row->fields['ACTIVE'] == 1) { ?>
                                                                <span class="active-box-green"></span>
                                                            <?php } else { ?>
                                                                <span class="active-box-red"></span>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                <?php $row->MoveNext();
                                                    $i++;
                                                }
                                            } else { ?>
                                                <tr>
                                                    <td colspan="9" style="text-align: center;">No locations selected. Please update your profile settings.</td>
                                                </tr>
                                            <?php } ?>
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

    <?php require_once('../includes/footer.php'); ?>
    <script>
        $(function() {
            $('#myTable').DataTable();
        });

        function ConfirmDelete(PK_SERVICE_MASTER) {
            var conf = confirm("Are you sure you want to delete?");
            if (conf) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {
                        FUNCTION_NAME: 'deleteServiceData',
                        PK_SERVICE_MASTER: PK_SERVICE_MASTER
                    },
                    success: function(data) {
                        window.location.href = `all_service_codes.php`;
                    }
                });
            }
        }

        function editpage(id) {
            // Check if multiple locations are selected
            <?php if ($multiple_locations) { ?>
                Swal.fire({
                    title: 'Multiple Locations Selected!',
                    html: 'You have selected <?= $location_count ?> locations. <br><br>Please select one location to edit Services / Service Codes.',
                    icon: 'warning',
                    cancelButtonText: 'Cancel',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33'
                })
            <?php } else { ?>
                window.location.href = "service_codes.php?id=" + id;
            <?php } ?>
        }

        function changeCountOnCalendar(PK_SERVICE_MASTER) {
            var checkbox = event.target;
            var countOnCalendar = checkbox.checked ? 1 : 0;

            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'updateCountOnCalendar',
                    PK_SERVICE_MASTER: PK_SERVICE_MASTER,
                    COUNT_ON_CALENDAR: countOnCalendar
                },
                success: function(data) {

                }
            });
        }

        // Handle Create New button click
        function createNewService() {
            <?php if ($multiple_locations) { ?>
                Swal.fire({
                    title: 'Multiple Locations Selected!',
                    html: `You have selected <strong><?= $location_count ?></strong> locations.<br><br>Please select one location to create a new Service / Service Code.`,
                    icon: 'warning',
                    cancelButtonText: 'Cancel',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33'
                })
            <?php } else { ?>
                window.location.href = 'service_codes.php';
            <?php } ?>
        }
    </script>
</body>

</html>