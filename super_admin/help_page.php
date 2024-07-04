<?error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
require_once("../global/config.php");
if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' ){
    header("location:../index.php");
    exit;
}

//$_GET['id'] ='';
if($_GET['id'] == ''){

    $TITLE 	 	  = '';
    $DESCRIPTION 	  = '';
    $ACTIVE = '';
} else {
    $res = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PK_HELP_PAGE = '$_GET[id]' ");
    if($res->RecordCount() == 0){
        header("location:help_page.php");
        exit;
    }
    $TITLE 		= $res->fields['TITLE'];
    $DESCRIPTION 	  		= $res->fields['DESCRIPTION'];
    $ACTIVE = '';
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
                    <h4 class="text-themecolor"><? if($_GET['id'] == '') echo "Add"; else echo "Edit"; ?> Knowledge Base </h4>
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
                                            <label for="NAME_ENG">Title (English)</label>
                                            <span class="bar"></span>
                                            <input type="text" class="form-control required-entry" id="NAME_ENG" name="NAME_ENG" value="<?=$NAME_ENG?>" >
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-12">
                                                Help Text (English)
                                            </div>
                                            <div class="col-md-12">
                                                <textarea class="form-control required-entry rich" rows="2" id="CONTENT_ENG" name="CONTENT_ENG"><?=$CONTENT_ENG?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

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

<link href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" rel="stylesheet" />
<script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js"></script>
<script src="https://cdn.tiny.cloud/1/d6quzxl18kigwmmr6z03zgk3w47922rw1epwafi19cfnj00i/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<script type="text/javascript">
    jQuery(document).ready(function($) {
        tinymce.init({
            selector:'.rich',
            browser_spellcheck: true,
            menubar: 'file edit view insert format tools table tc help',
            statusbar: false,
            height: '300',
            plugins: [
                'advlist lists hr pagebreak',
                'wordcount code',
                'nonbreaking save table contextmenu directionality',
                'template paste textcolor colorpicker textpattern '
            ],
            toolbar1: 'bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | forecolor backcolor',
            paste_data_images: true,
            height: 400,
        });
    });
</script>

</body>

</html>
