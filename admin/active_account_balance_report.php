<?php
require_once('../global/config.php');
$title = "Active Account Balance Report";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (!empty($_GET['SELECTED_DATE'])) {
    $type = isset($_GET['view']) ? 'view' : 'generate_excel';
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $SELECTED_DATE = $_GET['SELECTED_DATE'];
    $SELECTED_RANGE = $_GET['SELECTED_RANGE'];
    if ($generate_excel === 1) {
        $report_name = 'active_account_balance_report';
        header('location:excel_' . $report_name . '.php?selected_date=' . $SELECTED_DATE . '&selected_range=' . $SELECTED_RANGE . '&report_type=' . $report_name);
    } else {
        header('location:active_account_balance_report_details.php?selected_date=' . $SELECTED_DATE . '&selected_range=' . $SELECTED_RANGE . '&type=' . $type);
    }
    //header('location:active_account_balance_report_details.php?selected_date=' . $SELECTED_DATE . '&selected_range=' . $SELECTED_RANGE . '&type=' . $type);
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>
<style>
    .menu-list {
        list-style-type: none;
        margin-left: -30px;
    }

    .menu-list li {
        margin: 10px;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <?php require_once('../includes/report_menu.php') ?>
            <div class="container-fluid" style="padding: 10px 20px 0 20px;">
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
                                        <div class="col-2">
                                            <div class="form-group">
                                                <select class="form-control" name="SELECTED_RANGE" id="SELECTED_RANGE">
                                                    <option value="">Select Range</option>
                                                    <option value="1">1 Month Prior</option>
                                                    <option value="3">3 Months Prior</option>
                                                    <option value="6">6 Months Prior</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <?php if (in_array('Reports Create', $PERMISSION_ARRAY)) { ?>
                                                <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                                <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info" style="background-color: #39B54A !important;">
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
    <?php require_once('../includes/footer.php'); ?>
</body>

</html>
<script>
    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });
</script>