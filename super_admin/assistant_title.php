<?php
require_once('../global/config.php');
global $db;

$title = "Assistant Title";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    db_perform('DOA_OTHER_SETTING', $_POST, 'update'," PK_OTHER_SETTING =  1");
    header("location:assistant_title.php");
}

$res = $db->Execute("SELECT ASSISTANT_TITLE FROM `DOA_OTHER_SETTING` WHERE PK_OTHER_SETTING = 1");
if($res->RecordCount() == 0){
    header("location:assistant_title.php");
    exit;
}
$ASSISTANT_TITLE = $res->fields['ASSISTANT_TITLE'];
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
                            <li class="breadcrumb-item"><a href="all_chat_section.php">All Chat Section</a></li>
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
                                            <label class="col-md-12" for="example-text">Assistant Title<span class="text-danger">*</span></label>
                                            <div class="col-md-12">
                                                <input type="text" id="ASSISTANT_TITLE" name="ASSISTANT_TITLE" class="form-control" placeholder="Enter Chat Sections" required value="<?php echo $ASSISTANT_TITLE?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='assistant_title.php'">Cancel</button>
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