<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Text Template";
else
    $title = "Edit Text Template";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    $TEXT_ACCOUNT_DATA = $_POST;
    $TEXT_ACCOUNT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if ($_GET['id'] == '') {
        $TEXT_ACCOUNT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $TEXT_ACCOUNT_DATA['CREATED_ON'] = date("Y-m-d H:i");

        db_perform('DOA_TEXT_TEMPLATE', $TEXT_ACCOUNT_DATA, 'insert');
        header("location:all_text_templates.php");
    } else {
        $TEXT_ACCOUNT_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $TEXT_ACCOUNT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_TEXT_TEMPLATE', $TEXT_ACCOUNT_DATA, 'update', " PK_TEXT_TEMPLATE = '$_GET[id]'");
        header("location:all_text_templates.php");
    }

}

if (empty($_GET['id'])) {
    $TEMPLATE_NAME      = '';
    $SUBJECT            = '';
    $PK_EMAIL_TRIGGER     = '';
    $CONTENT            = '';
    $ACTIVE             = '';
} else {
    $res = $db->Execute("SELECT * FROM DOA_TEXT_TEMPLATE WHERE PK_TEXT_TEMPLATE = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_text_templates.php");
        exit;
    }
    $TEMPLATE_NAME      = $res->fields['TEMPLATE_NAME'];
    $SUBJECT            = $res->fields['SUBJECT'];
    $PK_EMAIL_TRIGGER     = $res->fields['PK_EMAIL_TRIGGER'];
    $CONTENT            = $res->fields['CONTENT'];
    $ACTIVE             = $res->fields['ACTIVE'];
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
        <div class="container-fluid extra-space body_content">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_text_templates.php">All Text Templates</a></li>
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
                                    <label for="TEMPLATE_NAME">Template Name</label>
                                    <input type="text" class="form-control" id="TEMPLATE_NAME" name="TEMPLATE_NAME" value="<?php echo $TEMPLATE_NAME ?>" required>
                                    <div class="invalid-feedback">
                                        Please Enter Template Name
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="SUBJECT">Subject</label>
                                    <input type="text" class="form-control" id="SUBJECT" name="SUBJECT" value="<?php echo $SUBJECT ?>" required>
                                    <div class="invalid-feedback">
                                        Please Enter Subject
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="PK_EMAIL_TRIGGER">Trigger</label>
                                    <select id="PK_EMAIL_TRIGGER" name="PK_EMAIL_TRIGGER" class="form-control">
                                        <option disabled selected>Select Event</option>
                                        <?php
                                        $row = $db->Execute("SELECT PK_EMAIL_TRIGGER, EMAIL_TRIGGER FROM DOA_EMAIL_TRIGGER WHERE ACTIVE = 1");
                                        while (!$row->EOF) {
                                            $selected = '';
                                            if($PK_EMAIL_TRIGGER!='' && $PK_EMAIL_TRIGGER == $row->fields['PK_EMAIL_TRIGGER']){
                                                $selected = 'selected';
                                            }
                                            ?>
                                            <option value="<?php echo $row->fields['PK_EMAIL_TRIGGER']; ?>" <?php echo $selected ;?>><?php echo $row->fields['EMAIL_TRIGGER']; ?></option>
                                            <?php $row->MoveNext(); } ?>
                                    </select>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="CONTENT">Content</label>
                                    <input type="text" class="form-control" id="CONTENT" name="CONTENT" value="<?php echo $CONTENT ?>" required>
                                    <div class="invalid-feedback">
                                        Please Enter Content.
                                    </div>
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