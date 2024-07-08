<?php
require_once("../global/config.php");

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' ){
    header("location:../index.php");
    exit;
}

if (empty($_GET['id']))
    $title = "Add Help Page";
else
    $title = "Edit Help Page";


if(!empty($_POST)){
    //echo "<pre>";print_r($_POST);exit;
    $HELP_PAGE = $_POST;
    if(empty($_GET['id'])){
        $HELP_PAGE['ACTIVE'] = 1;
        $HELP_PAGE['CREATED_BY']  = $_SESSION['PK_USER'];
        $HELP_PAGE['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_HELP_PAGE', $HELP_PAGE, 'insert');
        $PK_HELP_PAGE = $db->insert_ID();
    } else {
        $HELP_PAGE['EDITED_BY'] = $_SESSION['PK_USER'];
        $HELP_PAGE['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_HELP_PAGE', $HELP_PAGE, 'update'," PK_HELP_PAGE = '$_GET[id]'");
        $PK_HELP_PAGE = $_GET['id'];
    }
    header("location:manage_help_page.php");
}

//$_GET['id'] ='';
if(empty($_GET['id'])){
    $TITLE 	 	  = '';
    $PAGE_LINK = '';
    $DESCRIPTION 	  = '';
    $ACTIVE = '';
} else {
    $res = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PK_HELP_PAGE = '$_GET[id]' ");
    if($res->RecordCount() == 0){
        header("location:help_page.php");
        exit;
    }
    $TITLE 		= $res->fields['TITLE'];
    $PAGE_LINK = $res->fields['PAGE_LINK'];
    $DESCRIPTION 	  		= $res->fields['DESCRIPTION'];
    $ACTIVE = $res->fields['ACTIVE'];
}
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    .ck-editor__editable_inline {
        min-height: 300px;
    }
    .SumoSelect {
        width: 100%;
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
                    <h4 class="text-themecolor"><?php if(empty($_GET['id'])) echo "Add"; else echo "Edit"; ?> Help Page </h4>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form class="floating-labels m-t-40" method="post" name="form1" id="form1" enctype="multipart/form-data" >
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group m-b-40">
                                            <label for="TITLE">Title</label>
                                            <span class="bar"></span>
                                            <input type="text" class="form-control required-entry" id="TITLE" name="TITLE" value="<?=$TITLE?>" >
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group m-b-40">
                                            <label for="NAME_ENG">Page Link</label>
                                            <span class="bar"></span>
                                            <input type="text" class="form-control required-entry" id="PAGE_LINK" name="PAGE_LINK" value="<?=$PAGE_LINK?>" >
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-12">
                                                Help Text
                                            </div>
                                            <div class="col-md-12">
                                                <textarea class="ckeditor" rows="2" id="DESCRIPTION" name="DESCRIPTION"><?=$DESCRIPTION?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if(!empty($_GET['id'])) { ?>
                                    <div class="row" style="margin-top: 25px;">
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

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group m-b-5"  style="text-align:center;" >
                                            <br />
                                            <button type="submit" class="btn waves-effect waves-light btn-info">Submit</button>

                                            <button type="button" class="btn waves-effect waves-light" onclick="window.location.href='manage_help.php'" >Cancel</button>

                                        </div>
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
<script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/34.2.0/classic/ckeditor.js"></script>
</body>
<script>
    ClassicEditor
        .create( document.querySelector( '#DESCRIPTION' ) )
        .catch( error => {
            console.error( error );
        } );
</script>
</html>
