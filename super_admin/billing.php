<?php
require_once('../global/config.php');

$title = "Billing";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1) {
    header("location:../login.php");
    exit;
}

$AM_AMOUNT       = '';
$NOT_AM_AMOUNT       = '';

$res = $db->Execute("SELECT * FROM `DOA_OTHER_SETTING`");
if ($res->RecordCount() > 0) {
    $AM_AMOUNT       = $res->fields['AM_AMOUNT'];
    $NOT_AM_AMOUNT   = $res->fields['NOT_AM_AMOUNT'];
}

if (!empty($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] == 'saveBillingData') {
    $billing_info = $db->Execute("SELECT * FROM DOA_OTHER_SETTING");
    if ($billing_info->RecordCount() > 0) {
        $BILLING_DETAILS['AM_AMOUNT'] = $_POST['AM_AMOUNT'];
        $BILLING_DETAILS['NOT_AM_AMOUNT'] = $_POST['NOT_AM_AMOUNT'];
        $BILLING_DETAILS['EDITED_BY'] = $_SESSION['PK_USER'];
        $BILLING_DETAILS['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_OTHER_SETTING', $BILLING_DETAILS, 'update', " PK_OTHER_SETTING = " . '1');
    } else {
        $BILLING_DETAILS['CREATED_BY'] = $_SESSION['PK_USER'];
        $BILLING_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_OTHER_SETTING', $BILLING_DETAILS, 'insert');
    }

    header("location:billing.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <?php require_once('../includes/setup_menu_super_admin.php') ?>
            <div class="container-fluid body_content m-0">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-3 align-self-center text-end">
                    </div>
                    <div class="col-md-4 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form class="form-material form-horizontal" id="billingForm" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="FUNCTION_NAME" value="saveBillingData">
                                    <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?= $PK_ACCOUNT_MASTER ?>">
                                    <div class="p-20">
                                        <div class="row">
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="col-md-12">Arthur Murray Location Amount</label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="AM_AMOUNT" name="AM_AMOUNT" class="form-control" placeholder="Enter Amount" value="<?= $AM_AMOUNT ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="col-md-12">Not Arthur Murray Location Amount</label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="NOT_AM_AMOUNT" name="NOT_AM_AMOUNT" class="form-control" placeholder="Enter Amount" value="<?= $NOT_AM_AMOUNT ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Save</button>
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