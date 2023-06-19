<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Scheduling Codes";
else
    $title = "Edit Scheduling Codes";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    //$SCHEDULING_DATA = $_POST;
    $SCHEDULING_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if ($_GET['id'] == '') {
        $SCHEDULING_DATA['SCHEDULING_CODE'] = $_POST['SCHEDULING_CODE'];
        $SCHEDULING_DATA['SCHEDULING_NAME'] = $_POST['SCHEDULING_NAME'];
        $SCHEDULING_DATA['PK_SCHEDULING_EVENT'] = $_POST['PK_SCHEDULING_EVENT'];
        $SCHEDULING_DATA['PK_EVENT_ACTION'] = $_POST['PK_EVENT_ACTION'];
        $SCHEDULING_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $SCHEDULING_DATA['CREATED_ON'] = date("Y-m-d H:i");
        $SCHEDULING_DATA['ACTIVE'] = 1;
        db_perform('DOA_SCHEDULING_CODE', $SCHEDULING_DATA, 'insert');
        header("location:all_scheduling_codes.php");
    } else {
        $SCHEDULING_DATA['SCHEDULING_CODE'] = $_POST['SCHEDULING_CODE'];
        $SCHEDULING_DATA['SCHEDULING_NAME'] = $_POST['SCHEDULING_NAME'];
        $SCHEDULING_DATA['PK_SCHEDULING_EVENT'] = $_POST['PK_SCHEDULING_EVENT'];
        $SCHEDULING_DATA['PK_EVENT_ACTION'] = $_POST['PK_EVENT_ACTION'];
        $SCHEDULING_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $SCHEDULING_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $SCHEDULING_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_SCHEDULING_CODE', $SCHEDULING_DATA, 'update', " PK_SCHEDULING_CODE = '$_GET[id]'");
        header("location:all_scheduling_codes.php");
    }

}

if (empty($_GET['id'])) {
    $SCHEDULING_CODE      = '';
    $SCHEDULING_NAME            = '';
    $PK_SCHEDULING_EVENT     = '';
    $PK_EVENT_ACTION            = '';
    $ACTIVE             = '';
} else {
    $res = $db->Execute("SELECT * FROM DOA_SCHEDULING_CODE WHERE PK_SCHEDULING_CODE = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_scheduling_codes.php");
        exit;
    }
    $SCHEDULING_CODE      = $res->fields['SCHEDULING_CODE'];
    $SCHEDULING_NAME      = $res->fields['SCHEDULING_NAME'];
    $PK_SCHEDULING_EVENT  = $res->fields['PK_SCHEDULING_EVENT'];
    $PK_EVENT_ACTION   = $res->fields['PK_EVENT_ACTION'];
    $ACTIVE            = $res->fields['ACTIVE'];
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid extra-space">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_scheduling_codes.php">All Scheduling Codes</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row mb-20">
                <div class="col-8">
                    <div class="card">
                        <div class="card-body">
                            <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">

                                <div class="col-md-12 mb-3">
                                    <label for="SCHEDULING_CODE">Scheduling Code</label>
                                    <input type="text" class="form-control" id="SCHEDULING_CODE" name="SCHEDULING_CODE" value="<?php echo $SCHEDULING_CODE ?>" required>
                                    <div class="invalid-feedback">
                                        Enter Scheduling Code
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="SCHEDULING_NAME">Scheduling Name</label>
                                    <input type="text" class="form-control" id="SCHEDULING_NAME" name="SCHEDULING_NAME" value="<?php echo $SCHEDULING_NAME ?>" required>
                                    <div class="invalid-feedback">
                                        Enter Scheduling Name
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="PK_SCHEDULING_EVENT">Scheduling Event</label>
                                    <select id="PK_SCHEDULING_EVENT" name="PK_SCHEDULING_EVENT" class="form-control">
                                        <option disabled selected>Select Scheduling Event</option>
                                        <?php
                                        $row = $db->Execute("SELECT PK_SCHEDULING_EVENT, SCHEDULING_EVENT FROM DOA_SCHEDULING_EVENT WHERE ACTIVE = 1");
                                        while (!$row->EOF) {
                                            $selected = '';
                                            if($PK_SCHEDULING_EVENT!='' && $PK_SCHEDULING_EVENT == $row->fields['PK_SCHEDULING_EVENT']){
                                                $selected = 'selected';
                                            }
                                            ?>
                                            <option value="<?php echo $row->fields['PK_SCHEDULING_EVENT']; ?>" <?php echo $selected ;?>><?php echo $row->fields['SCHEDULING_EVENT']; ?></option>
                                            <?php $row->MoveNext(); } ?>
                                    </select>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="PK_EVENT_ACTION">Event Action</label>
                                    <select id="PK_EVENT_ACTION" name="PK_EVENT_ACTION" class="form-control">
                                        <option disabled selected>Select Event Action</option>
                                        <?php
                                        $row = $db->Execute("SELECT PK_EVENT_ACTION, EVENT_ACTION FROM DOA_EVENT_ACTION WHERE ACTIVE = 1");
                                        while (!$row->EOF) {
                                            $selected = '';
                                            if($PK_EVENT_ACTION!='' && $PK_EVENT_ACTION == $row->fields['PK_EVENT_ACTION']){
                                                $selected = 'selected';
                                            }
                                            ?>
                                            <option value="<?php echo $row->fields['PK_EVENT_ACTION']; ?>" <?php echo $selected ;?>><?php echo $row->fields['EVENT_ACTION']; ?></option>
                                            <?php $row->MoveNext(); } ?>
                                    </select>
                                </div>

                                <?php if(!empty($_GET['id'])){?>
                                    <input type="hidden" name="PK_TEXT_TEMPLATE" value="<?php echo $_GET['id'] ?>">
                                    <div class="col-md-12 mb-3">
                                        <label for="IMAGE">Active</label>
                                        <div class="custom-control custom-radio ">
                                            <input type="radio" id="ACTIVE1" name="ACTIVE" class="custom-control-input" <?php echo $ACTIVE == '1'?'checked':'' ?> value="1">
                                            <label class="custom-control-label" for="ACTIVE1">Yes</label>
                                        </div>
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="ACTIVE2" name="ACTIVE" class="custom-control-input" <?php echo $ACTIVE == '0'?'checked':'' ?> value="0">
                                            <label class="custom-control-label" for="ACTIVE2">No</label>
                                        </div>
                                    </div>
                                <?php } ?>


                                <button class="btn btn-info waves-effect waves-light m-r-10 text-white" type="submit"> <?php if(empty($_GET['id'])){ echo 'Save'; } else { echo 'Update'; }?></button>
                                <button class="btn btn-inverse waves-effect waves-light" type="button" onclick="window.location.href='all_scheduling_codes.php'" >Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" id="row_id" value="" />
    </div>
</div>
<?php require_once('../includes/footer.php');?>
</body>
</html>