<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

if (empty($_GET['id']))
    $title = "Terms of Use";
else
    $title = "Edit Terms of Use";


$help_title = '';
$help_description = '';
$help = $db->Execute("SELECT * FROM DOA_TERMS_OF_USE WHERE PAGE_LINK = 'terms_of_use'");
if($help->RecordCount() > 0) {
    $help_title = $help->fields['TITLE'];
    $help_description = $help->fields['DESCRIPTION'];
}

?>

<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<?php require_once('../includes/header.php');?>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet"/>
<body class="skin-default-dark fixed-layout">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <h4 class="col-md-12" STYLE="text-align: center">
                                    <?=$help_title?>
                                </h4>
                                <div class="col-md-12">
                                    <text class="required-entry rich" id="DESCRIPTION"><?=$help_description?></text>
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
