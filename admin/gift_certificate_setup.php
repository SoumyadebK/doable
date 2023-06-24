<?php
require_once('../global/config.php');

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ) {
    header("location:../login.php");
    exit;
}

if (empty($_GET['id'])) {
    $title = "Gift Certificate Setup";
} else {
    $title = "Edit Gift Certificate";
}

if (!empty($_POST)) {
    $GIFT_CERTIFICATE_SETUP_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if (empty($_GET['id'])) {
        $GIFT_CERTIFICATE_SETUP_DATA['GIFT_CERTIFICATE_CODE'] = $_POST['GIFT_CERTIFICATE_CODE'];
        $GIFT_CERTIFICATE_SETUP_DATA['GIFT_CERTIFICATE_NAME'] = $_POST['GIFT_CERTIFICATE_NAME'];
        $GIFT_CERTIFICATE_SETUP_DATA['MINIMUM_AMOUNT'] = $_POST['MINIMUM_AMOUNT'];
        $GIFT_CERTIFICATE_SETUP_DATA['MAXIMUM_AMOUNT'] = $_POST['MAXIMUM_AMOUNT'];
        $GIFT_CERTIFICATE_SETUP_DATA['EFFECTIVE_DATE'] = date('Y-m-d', strtotime($_POST['EFFECTIVE_DATE']));
        $GIFT_CERTIFICATE_SETUP_DATA['END_DATE'] = date('Y-m-d', strtotime($_POST['END_DATE']));
        $GIFT_CERTIFICATE_SETUP_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $GIFT_CERTIFICATE_SETUP_DATA['CREATED_ON'] = date("Y-m-d H:i");
        $GIFT_CERTIFICATE_SETUP_DATA['ACTIVE'] = 1;
        db_perform('DOA_GIFT_CERTIFICATE_SETUP', $GIFT_CERTIFICATE_SETUP_DATA, 'insert');
        header("location:all_gift_certificate_setup.php");
    } else {
        $GIFT_CERTIFICATE_SETUP_DATA['GIFT_CERTIFICATE_CODE'] = $_POST['GIFT_CERTIFICATE_CODE'];
        $GIFT_CERTIFICATE_SETUP_DATA['GIFT_CERTIFICATE_NAME'] = $_POST['GIFT_CERTIFICATE_NAME'];
        $GIFT_CERTIFICATE_SETUP_DATA['MINIMUM_AMOUNT'] = $_POST['MINIMUM_AMOUNT'];
        $GIFT_CERTIFICATE_SETUP_DATA['MAXIMUM_AMOUNT'] = $_POST['MAXIMUM_AMOUNT'];
        $GIFT_CERTIFICATE_SETUP_DATA['EFFECTIVE_DATE'] = date('Y-m-d', strtotime($_POST['EFFECTIVE_DATE']));
        $GIFT_CERTIFICATE_SETUP_DATA['END_DATE'] = date('Y-m-d', strtotime($_POST['END_DATE']));
        $GIFT_CERTIFICATE_SETUP_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $GIFT_CERTIFICATE_SETUP_DATA['EDITED_ON'] = date("Y-m-d H:i");
        $GIFT_CERTIFICATE_SETUP_DATA['ACTIVE'] = $_POST['ACTIVE'];
        db_perform('DOA_GIFT_CERTIFICATE_SETUP', $GIFT_CERTIFICATE_SETUP_DATA, 'update', "PK_GIFT_CERTIFICATE_SETUP = '$_GET[id]'");
        header("location:all_gift_certificate_setup.php");
    }
}

if (empty($_GET['id'])) {
    $PK_USER_MASTER = '';
    $GIFT_CERTIFICATE_CODE ='';
    $GIFT_CERTIFICATE_NAME ='';
    $MINIMUM_AMOUNT = '';
    $MAXIMUM_AMOUNT = '';
    $EFFECTIVE_DATE = '';
    $END_DATE = '';
    $ACTIVE = '';
} else {
    $res = $db->Execute("SELECT * FROM DOA_GIFT_CERTIFICATE_SETUP WHERE PK_GIFT_CERTIFICATE_SETUP = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_gift_certificate_setup.php");
        exit;
    }
    $GIFT_CERTIFICATE_CODE = $res->fields['GIFT_CERTIFICATE_CODE'];
    $GIFT_CERTIFICATE_NAME = $res->fields['GIFT_CERTIFICATE_NAME'];
    $MINIMUM_AMOUNT = $res->fields['MINIMUM_AMOUNT'];
    $MAXIMUM_AMOUNT = $res->fields['MAXIMUM_AMOUNT'];
    $EFFECTIVE_DATE = $res->fields['EFFECTIVE_DATE'];
    $END_DATE = $res->fields['END_DATE'];
    $ACTIVE = $res->fields['ACTIVE'];
}

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=PT+Mono&display=swap" rel="stylesheet">
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_gift_certificate_setup.php">All Gift Certificate Setup</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="active"> <a class="nav-link active" data-bs-toggle="tab" id="gift_certificate_link" href="#gift_certificate" role="tab"><span class="hidden-sm-up"><i class="ti-pencil-alt"></i></span> <span class="hidden-xs-down">Gift Certificate Setup</span></a> </li>
                            </ul>

                            <div class="tab-content tabcontent-border">
                                <div class="tab-pane active" id="gift_certificate" role="tabpanel">
                                    <form id="gift_certificate_form" action="" method="post" enctype="multipart/form-data">
                                        <div class="p-20">
                                            <div class="row">
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">Gift Certificate Name</label>
                                                        <div>
                                                            <input type="text" id="GIFT_CERTIFICATE_NAME" name="GIFT_CERTIFICATE_NAME" class="form-control" placeholder="Enter Gift Certificate Name" required value="<?php echo $GIFT_CERTIFICATE_NAME?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">Gift Certificate Code<span class="text-danger">*</span></label>
                                                        <div>
                                                            <input type="text" id="GIFT_CERTIFICATE_CODE" name="GIFT_CERTIFICATE_CODE" class="form-control" placeholder="Enter Gift Certificate Code" required value="<?php echo $GIFT_CERTIFICATE_CODE?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">Effective Date</label>
                                                        <div class="col-md-12">
                                                            <input type="text" name="EFFECTIVE_DATE" id="EFFECTIVE_DATE" value="<?=($EFFECTIVE_DATE == '')?date('m/d/Y'):date('m/d/Y', strtotime($EFFECTIVE_DATE))?>" class="form-control datepicker-normal">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">End Date</label>
                                                        <div class="col-md-12">
                                                            <input type="text" name="END_DATE" id="END_DATE" value="<?=($END_DATE == '')?date('m/d/Y'):date('m/d/Y', strtotime($END_DATE))?>" class="form-control datepicker-normal">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">Minimum Amount (Optional)</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="MINIMUM_AMOUNT" name="MINIMUM_AMOUNT" class="form-control" placeholder="Enter Minimum Amount" required value="<?php echo $MINIMUM_AMOUNT?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">Maximum Amount (Optional)</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="MAXIMUM_AMOUNT" name="MAXIMUM_AMOUNT" class="form-control" placeholder="Enter Maximum Amount" required value="<?php echo $MAXIMUM_AMOUNT?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                <?php if(!empty($_GET['id'])) { ?>
                                    <div class="row" style="margin-bottom: 15px;">
                                        <div class="col-6">
                                            <div class="col-md-2">
                                                <label>Active</label>
                                            </div>
                                            <div class="col-md-4">
                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                            </div>
                                        </div>
                                    </div>
                                <? } ?>

                                <div class="form-group">
                                    <button class="btn btn-info waves-effect waves-light m-r-10 text-white" type="submit"> <?php if(empty($_GET['id'])){ echo 'Save'; } else { echo 'Update'; }?></button>
                                    <button class="btn btn-inverse waves-effect waves-light" type="button" onclick="window.location.href='all_gift_certificate_setup.php'" >Cancel</button>
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
</div>
</div>

<?php require_once('../includes/footer.php');?>

<script src="https://js.stripe.com/v3/"></script>

<script>
    $('.datepicker-future').datepicker({
        format: 'mm/dd/yyyy',
        minDate: 0
    });

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });
</script>
</body>
</html>