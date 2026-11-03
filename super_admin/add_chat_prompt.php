<?php
require_once('../global/config.php');
global $db;

if (empty($_GET['id']))
    $title = "Add Chat Prompt";
else
    $title = "Edit Chat Prompt";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $PROMPT_DATA = $_POST;
    if(empty($_GET['id'])){
        $PROMPT_DATA['ACTIVE'] = 1;
        $PROMPT_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $PROMPT_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_AI_CHAT_PROMPT', $PROMPT_DATA, 'insert');
        $PK_AI_CHAT_PROMPT = $db->insert_ID();
    }else{
        $PROMPT_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $PROMPT_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $PROMPT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_AI_CHAT_PROMPT', $PROMPT_DATA, 'update'," PK_AI_CHAT_PROMPT =  '$_GET[id]'");
        $PK_AI_CHAT_PROMPT = $_GET['id'];
    }

    header("location:all_chat_prompt.php");
}

$PK_AI_CHAT_SECTION = '';
$PROMPT = '';
$ACTIVE = '';

if(!empty($_GET['id'])) {
    $res = $db->Execute("SELECT * FROM `DOA_AI_CHAT_PROMPT` WHERE PK_AI_CHAT_PROMPT = '$_GET[id]'");
    if($res->RecordCount() == 0){
        header("location:all_chat_prompt.php");
        exit;
    }
    $PK_AI_CHAT_SECTION = $res->fields['PK_AI_CHAT_SECTION'];
    $PROMPT = $res->fields['PROMPT'];
    $ACTIVE = $res->fields['ACTIVE'];
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
        <div class="container-fluid body_content">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_chat_prompt.php">All Chat Prompt</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <form class="form-material form-horizontal m-t-30" name="form1" id="form1" action="" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="form-label">Section Name<span class="text-danger">*</span></label>
                                            <div class="col-md-12">
                                                <select class="form-control" name="PK_AI_CHAT_SECTION" id="PK_AI_CHAT_SECTION" required>
                                                    <option value="">Select Section</option>
                                                    <?php
                                                    $row = $db->Execute("SELECT * FROM `DOA_AI_CHAT_SECTION` WHERE ACTIVE = 1");
                                                    while (!$row->EOF) { ?>
                                                        <option value="<?php echo $row->fields['PK_AI_CHAT_SECTION'];?>" <?=($row->fields['PK_AI_CHAT_SECTION'] == $PK_AI_CHAT_SECTION) ? "selected" : ""?>><?=$row->fields['SECTION_NAME']?></option>
                                                    <?php $row->MoveNext(); } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Prompt<span class="text-danger">*</span></label>
                                            <div class="col-md-12">
                                                <input type="text" id="PROMPT" name="PROMPT" class="form-control" placeholder="Enter Chat Prompt" required value="<?php echo $PROMPT?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if(!empty($_GET['id'])) { ?>
                                    <div class="row" style="margin-bottom: 15px; margin-top: 10px">
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
                                <?php } ?>

                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_chat_prompt.php'">Cancel</button>
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