<?php
require_once('../global/config.php');
$title = "Payments Do. Report";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

if (!empty($_GET['SELECTED_DATE'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $SELECTED_DATE = $_GET['SELECTED_DATE'];
    header('location:payments_do_report_details.php?selected_date=' . $SELECTED_DATE . '&type=' . $type);
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    .menu-list{
        list-style-type: none;
        margin-left: -30px;
    }

    .menu-list li{
        margin: 10px;
    }
</style>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="reports.php">All Reports</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="row" style="padding: 15px 15px 15px 15px;">
                            <form class="form-material form-horizontal" action="" method="get">
                                <input type="hidden" name="selected_date" id="selected_date">
                                <div class="row">
                                    <div class="col-2">
                                        <div class="form-group">
                                            <input type="text" id="SELECTED_DATE" name="SELECTED_DATE" class="form-control datepicker-normal" placeholder="Select Date" value="<?= !empty($_GET['SELECTED_DATE']) ? $_GET['SELECTED_DATE'] : '' ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <?php if(in_array('Reports Create', $PERMISSION_ARRAY)){ ?>
                                            <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                        <?php } ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once('../includes/footer.php');?>
</body>
</html>
<script>
    $('.datepicker-normal').datepicker({
    format: 'mm/dd/yyyy',
    });
</script>