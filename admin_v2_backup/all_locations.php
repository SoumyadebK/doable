<?php
require_once('../global/config.php');
$title = "All Locations";

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Locations page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">

        <div class="page-wrapper" style="padding-top: 0px !important;">

            <?php require_once('layout/setup_menu.php') ?>
            <div class="container-fluid body_content m-0" style="margin-top: 0px !important;">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='location.php'"><i class="fa fa-plus-circle"></i> Create New</button>
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
                                    <table id="myTable" class="table table-striped border" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                <th>Location ID</th>
                                                <th>Location Name</th>
                                                <th>Corporation Name</th>
                                                <th>City</th>
                                                <th>Phone</th>
                                                <th>Email</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                            $i = 1;
                                            $row = $db->Execute("SELECT DOA_LOCATION.*, DOA_CORPORATION.CORPORATION_NAME FROM `DOA_LOCATION` LEFT JOIN DOA_CORPORATION ON DOA_LOCATION.PK_CORPORATION=DOA_CORPORATION.PK_CORPORATION WHERE DOA_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_LOCATION.PK_ACCOUNT_MASTER='$_SESSION[PK_ACCOUNT_MASTER]'");
                                            while (!$row->EOF) { ?>
                                                <tr>
                                                    <td onclick="editpage(<?= $row->fields['PK_LOCATION'] ?>);"><?= $row->fields['PK_LOCATION'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_LOCATION'] ?>);"><?= $row->fields['LOCATION_NAME'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_LOCATION'] ?>);"><?= $row->fields['CORPORATION_NAME'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_LOCATION'] ?>);"><?= $row->fields['CITY'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_LOCATION'] ?>);"><?= $row->fields['PHONE'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_LOCATION'] ?>);"><?= $row->fields['EMAIL'] ?></td>
                                                    <td>
                                                        <a href="location.php?id=<?= $row->fields['PK_LOCATION'] ?>" title="Edit" style="font-size:18px"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <a href="all_locations.php?type=del&id=<?= $row->fields['PK_LOCATION'] ?>" onclick="javascript:ConfirmDelete(<?= $row->fields['PK_LOCATION'] ?>);" title="Delete" style="font-size:18px"><i class="fa fa-trash"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
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

        function ConfirmDelete(PK_LOCATION) {
            var conf = confirm("Are you sure you want to delete?");
            if (conf) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {
                        FUNCTION_NAME: 'deleteLocationData',
                        PK_LOCATION: PK_LOCATION
                    },
                    success: function(data) {
                        window.location.href = `all_locations.php`;
                    }
                });
            }
        }

        function editpage(id) {
            //alert(i);
            window.location.href = "location.php?id=" + id;
        }
    </script>
</body>

</html>