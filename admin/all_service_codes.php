<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

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
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='service_codes.php'"><i class="fa fa-plus-circle"></i> Create New</button>
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
                                                <th style="text-align: center">Sort Order</th>
                                                <th style="text-align: center">Actions</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                            $i = 1;
                                            $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE, DOA_SERVICE_CODE.COUNT_ON_CALENDAR, DOA_SERVICE_CODE.SORT_ORDER, DOA_LOCATION.LOCATION_NAME FROM `DOA_SERVICE_MASTER` LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_SERVICE_MASTER.PK_LOCATION = DOA_LOCATION.PK_LOCATION WHERE DOA_SERVICE_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND IS_DELETED = 0 AND DOA_SERVICE_MASTER.ACTIVE = '$status' ORDER BY DOA_SERVICE_MASTER.SERVICE_NAME ASC");
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
                                                        <a href="service_codes.php?id=<?= $row->fields['PK_SERVICE_MASTER'] ?>" title="Edit" style="font-size:18px"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <a href="all_services.php?type=del&id=<?= $row->fields['PK_SERVICE_MASTER'] ?>" onclick="ConfirmDelete(<?= $row->fields['PK_SERVICE_MASTER'] ?>);" title="Delete" style="font-size:18px"><i class="fa fa-trash"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <?php if ($row->fields['ACTIVE'] == 1) { ?>
                                                            <span class="active-box-green"></span>
                                                        <?php } else { ?>
                                                            <span class="active-box-red"></span>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php $row->MoveNext();
                                                $i++;
                                            } ?>
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
            window.location.href = "service_codes.php?id=" + id;
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
    </script>
</body>

</html>