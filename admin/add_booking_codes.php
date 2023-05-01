<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Booking Codes";
else
    $title = "Edit Booking Codes";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    //$BOOKING_DATA = $_POST;
    $BOOKING_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if ($_GET['id'] == '') {
        $BOOKING_DATA['BOOKING_CODE'] = $_POST['BOOKING_CODE'];
        $BOOKING_DATA['BOOKING_NAME'] = $_POST['BOOKING_NAME'];
        $BOOKING_DATA['PK_BOOKING_EVENT'] = $_POST['PK_BOOKING_EVENT'];
        $BOOKING_DATA['PK_EVENT_ACTION'] = $_POST['PK_EVENT_ACTION'];
        $BOOKING_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $BOOKING_DATA['CREATED_ON'] = date("Y-m-d H:i");
        $BOOKING_DATA['ACTIVE'] = 1;
        db_perform('DOA_BOOKING_CODES', $BOOKING_DATA, 'insert');
        header("location:all_booking_codes.php");
    } else {
        $BOOKING_DATA['BOOKING_CODE'] = $_POST['BOOKING_CODE'];
        $BOOKING_DATA['BOOKING_NAME'] = $_POST['BOOKING_NAME'];
        $BOOKING_DATA['PK_BOOKING_EVENT'] = $_POST['PK_BOOKING_EVENT'];
        $BOOKING_DATA['PK_EVENT_ACTION'] = $_POST['PK_EVENT_ACTION'];
        $BOOKING_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $BOOKING_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $BOOKING_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_BOOKING_CODES', $BOOKING_DATA, 'update', " PK_BOOKING_CODES = '$_GET[id]'");
        header("location:all_booking_codes.php");
    }

}

if (empty($_GET['id'])) {
    $BOOKING_CODE      = '';
    $BOOKING_NAME            = '';
    $PK_BOOKING_EVENT     = '';
    $PK_EVENT_ACTION            = '';
    $ACTIVE             = '';
} else {
    $res = $db->Execute("SELECT * FROM DOA_BOOKING_CODES WHERE PK_BOOKING_CODES = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_booking_codes.php");
        exit;
    }
    $BOOKING_CODE      = $res->fields['BOOKING_CODE'];
    $BOOKING_NAME      = $res->fields['BOOKING_NAME'];
    $PK_BOOKING_EVENT  = $res->fields['PK_BOOKING_EVENT'];
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
                            <li class="breadcrumb-item"><a href="all_booking_codes.php">All Booking Codes</a></li>
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
                                    <label for="BOOKING_CODE">Booking Code</label>
                                    <input type="text" class="form-control" id="BOOKING_CODE" name="BOOKING_CODE" value="<?php echo $BOOKING_CODE ?>" required>
                                    <div class="invalid-feedback">
                                        Enter Booking Code
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="BOOKING_NAME">Booking Name</label>
                                    <input type="text" class="form-control" id="BOOKING_NAME" name="BOOKING_NAME" value="<?php echo $BOOKING_NAME ?>" required>
                                    <div class="invalid-feedback">
                                        Enter Booking Name
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="PK_BOOKING_EVENT">Booking Event</label>
                                    <select id="PK_BOOKING_EVENT" name="PK_BOOKING_EVENT" class="form-control">
                                        <option disabled selected>Select Booking Event</option>
                                        <?php
                                        $row = $db->Execute("SELECT PK_BOOKING_EVENT, BOOKING_EVENT FROM DOA_BOOKING_EVENT WHERE ACTIVE = 1");
                                        while (!$row->EOF) {
                                            $selected = '';
                                            if($PK_BOOKING_EVENT!='' && $PK_BOOKING_EVENT == $row->fields['PK_BOOKING_EVENT']){
                                                $selected = 'selected';
                                            }
                                            ?>
                                            <option value="<?php echo $row->fields['PK_BOOKING_EVENT']; ?>" <?php echo $selected ;?>><?php echo $row->fields['BOOKING_EVENT']; ?></option>
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
                                <button class="btn btn-inverse waves-effect waves-light" type="button" onclick="window.location.href='all_text_templates.php'" >Cancel</button>
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