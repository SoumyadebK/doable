<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Document Library";
else
    $title = "Edit Document Library";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $ONBOARDING_DOCUMENT = $_POST;
    $ONBOARDING_DOCUMENT['PK_LOCATION'] = implode(',', $_POST['PK_LOCATION']);
    $ONBOARDING_DOCUMENT['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];

    if(empty($_GET['id'])){
        $ONBOARDING_DOCUMENT['ACTIVE'] = 1;
        $ONBOARDING_DOCUMENT['CREATED_BY']  = $_SESSION['PK_USER'];
        $ONBOARDING_DOCUMENT['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_DOCUMENT_LIBRARY', $ONBOARDING_DOCUMENT, 'insert');
    }else{
        $ONBOARDING_DOCUMENT['ACTIVE'] = $_POST['ACTIVE'];
        $ONBOARDING_DOCUMENT['EDITED_BY']	= $_SESSION['PK_USER'];
        $ONBOARDING_DOCUMENT['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_DOCUMENT_LIBRARY', $ONBOARDING_DOCUMENT, 'update'," PK_DOCUMENT_LIBRARY =  '$_GET[id]'");
    }
    header("location:all_document_library.php");
}



if(empty($_GET['id'])){
    $DOCUMENT_NAME = '';
    $PK_DOCUMENT_TYPE = '';
    $PK_LOCATION = '';
    $DOCUMENT_TEMPLATE = '';
    $ACTIVE = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_DOCUMENT_LIBRARY` WHERE `PK_DOCUMENT_LIBRARY` = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_document_library.php");
        exit;
    }

    $DOCUMENT_NAME = $res->fields['DOCUMENT_NAME'];
    $PK_DOCUMENT_TYPE = $res->fields['PK_DOCUMENT_TYPE'];
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $DOCUMENT_TEMPLATE = $res->fields['DOCUMENT_TEMPLATE'];
    $ACTIVE = $res->fields['ACTIVE'];
}

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet"/>
<style>
    #cke_1_contents {
        min-height: 400px;
    }
</style>
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
                            <li class="breadcrumb-item"><a href="all_document_library.php">Document Library</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="form-group">
                                        <label class="form-label">Document Name<span class="text-danger">*</span></label>
                                        <div class="col-md-6">
                                            <input type="text" id="DOCUMENT_NAME" name="DOCUMENT_NAME" class="form-control" placeholder="Enter Document Name" required value="<?php echo $DOCUMENT_NAME?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group">
                                        <label class="form-label">Document Type</label>
                                        <div class="col-md-6">
                                            <select class="form-control" name="PK_DOCUMENT_TYPE" id="PK_DOCUMENT_TYPE">
                                                <option>Select Document Type</option>
                                                <?php
                                                $row = $db->Execute("SELECT * FROM DOA_DOCUMENT_TYPE WHERE ACTIVE = 1");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_DOCUMENT_TYPE'];?>" <?=($row->fields['PK_DOCUMENT_TYPE'] == $PK_DOCUMENT_TYPE)?"selected":""?>><?=$row->fields['DOCUMENT_TYPE']?></option>
                                                    <?php $row->MoveNext(); } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="form-label">Location</label>
                                    <div class="col-md-9" style="margin-bottom: 15px;">
                                        <select name="PK_LOCATION[]" id="PK_LOCATION" multiple>
                                            <?php
                                            $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                            while (!$row->EOF) { ?>
                                                <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=in_array($row->fields['PK_LOCATION'], explode(',', $PK_LOCATION))?"selected":""?>><?=$row->fields['LOCATION_NAME']?></option>
                                                <?php $row->MoveNext(); } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group">
                                        <label class="form-label">Tag Name</label>
                                        <div class="col-md-6" style="padding-top: 10px; padding-left: 10px;">
                                            <a href="javascript:;" class="tag_name" data-tag="{USER_NAME}" style="font-weight: normal;">{USER_NAME}</a>
                                            <a href="javascript:;" class="tag_name" data-tag="{USER_PHONE}" style="font-weight: normal;">{USER_PHONE}</a>
                                            <a href="javascript:;" class="tag_name" data-tag="{USER_EMAIL}" style="font-weight: normal;">{USER_EMAIL}</a>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group">
                                        <label class="form-label">Template</label>
                                        <div class="col-md-12">
                                            <textarea id="ck_editor" rows="20" name="DOCUMENT_TEMPLATE"><?=$DOCUMENT_TEMPLATE?></textarea>
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

                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_document_library.php'">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php');?>

<script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>


<script src="https://cdn.ckeditor.com/4.18.0/standard/ckeditor.js"></script>
<script>
    $('#PK_LOCATION').SumoSelect({placeholder: 'Select Location', selectAll: true});

    const editor = CKEDITOR.replace('ck_editor');

    $(document).on('click', '.tag_name', function () {
        let tag_name = $(this).data('tag');
        editor.insertText(tag_name);
    });
</script>
</body>
</html>