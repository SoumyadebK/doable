<?php
require_once("../global/config.php");
global $db;
global $db_account;

$title = "E-mail";

require_once("../global/config.php");
if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' ){
    header("location:../index.php");
    exit;
}

$id = empty($_GET['id']) ? '' : $_GET['id'];
$type = empty($_GET['type']) ? '' : $_GET['type'];
$mail_type = empty($_GET['mail_type']) ? '' : $_GET['mail_type'];

// require_once("email_variables.php");

if(!empty($_GET['act']) && $_GET['act'] == 'i'){
    $db_account->Execute("UPDATE DOA_EMAIL_RECEPTION SET DELETED = 0 WHERE INTERNAL_ID = '$_GET[id]' AND PK_USER = '$_SESSION[PK_USER]' ");
    header("location:email.php");
}

$res_pk   = $db_account->Execute("select PK_EMAIL from DOA_EMAIL WHERE INTERNAL_ID = '$_GET[id]' ORDER BY PK_EMAIL DESC");
$PK_EMAIL = $res_pk->fields['PK_EMAIL'];

$res = $db_account->Execute("select SUBJECT from DOA_EMAIL WHERE PK_EMAIL = '$_GET[id]' ");
$db_account->Execute("UPDATE DOA_EMAIL_RECEPTION SET VIWED = 1 WHERE INTERNAL_ID = '$_GET[id]' AND PK_USER = '$_SESSION[PK_USER]' ");
$em_subject = $res->fields['SUBJECT'];

$res_att = $db_account->Execute("SELECT * FROM DOA_EMAIL_STARRED WHERE INTERNAL_ID = '$_GET[id]' AND STARRED = 1 AND PK_USER = '$_SESSION[PK_USER]' ")or die(mysql_error());
if($res_att->RecordCount() > 0)
    $color = 'gold';
else
    $color = '#DDDDDD';

?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<body class="skin-default-dark fixed-layout">
<?php //require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content">
            <div class="main" >
                <div class="main-inner">
                    <div class="container">
                        <div class="row pt-2 pb-5">
                            <div class="span2 col-md-2">
                                <div class="widget widget-nopad">
                                    <!-- <div class="widget-header"> <i class="icon-list-alt"></i>
                                        <h3> Internal Mail</h3>
                                    </div> -->

                                    <div class="widget-content">
                                        <div class="widget big-stats-container">
                                            <?php require_once("menu_left_menu.php"); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="span10 col-md-10">
                                <div class="card">
                                    <div class="card-body">
                                        <form name="form1" id="form1" method="post">
                                            <div class="widget">
                                                <div class="widget-header" >
                                                    <div class="span9" style="font-weight:bold;" >
                                                        <i class="icon-star" id="star_id" onclick="star(<?=$_GET['id']?>)" style="font-size:15px;color:<?=$color?>"></i> &nbsp;<?=$em_subject;?>
                                                    </div>
                                                </div>
                                                <div class="widget-content" style="padding-top: 10px">
                                                    <div class="row">
                                                        <div class="span9">
                                                            <?php if($_GET['type'] != 'trash') { ?>
                                                                <?php if($_GET['type'] != 'draft') { ?>
                                                                    <a style="float:right;margin-right:5px;" class="btn btn-info" href="compose.php?id=<?=$_GET['id']?>&pk=<?=$PK_EMAIL?>&type=reply"><i class="fa fa-reply"></i> Reply</a>
                                                                <?php } ?>
                                                                    <a style="float:right;margin-right:5px;" class="btn btn-info" href="compose.php?id=<?=$_GET['id']?>&pk=<?=$PK_EMAIL?>&type=forward"><i class="fa fa-forward"></i> Forward</a>
                                                            <?php } ?>

                                                            <?php if($_GET['type'] == 'trash') { ?>
                                                                <a style="float:right;;margin-right:5px;" class="btn btn-info" href="view_email.php?type=trash&id=<?=$_GET['id']?>&act=i"><i class="fa fa-inbox"></i> Move To Inbox</a>
                                                            <?php } ?>
                                                        </div>
                                                    </div>


                                                    <?php if($_GET['type'] == 'sent' || $_GET['type'] == 'draft' || $_GET['type'] == 'starred'){
                                                        $res = $db_account->Execute("select DOA_EMAIL.PK_EMAIL, CONTENT,IF(REMINDER_DATE != '0000-00-00', DATE_FORMAT(REMINDER_DATE, '%m/%d/%Y'),'' ) AS REMINDER_DATE ,IF(DUE_DATE != '0000-00-00', DATE_FORMAT(DUE_DATE, '%m/%d/%Y'),'' ) AS DUE_DATE, IF(DOA_EMAIL.CREATED_ON != '0000-00-00', DATE_FORMAT(DOA_EMAIL.CREATED_ON, '%m/%d/%Y %r'),'' ) AS CREATED_ON,DOA_USERS.FIRST_NAME from DOA_EMAIL, DOA_USERS WHERE DOA_EMAIL.INTERNAL_ID = '$_GET[id]' AND DOA_EMAIL.CREATED_BY = '$_SESSION[PK_USER]' AND DOA_USERS.PK_USER = DOA_EMAIL.CREATED_BY ORDER BY DOA_EMAIL.CREATED_ON DESC");

                                                    } else if($_GET['type'] == '') {
                                                        $res = $db_account->Execute("select PK_EMAIL_RECEPTION,DOA_EMAIL.PK_EMAIL, CONTENT,IF(REMINDER_DATE != '0000-00-00', DATE_FORMAT(REMINDER_DATE, '%m/%d/%Y'),'' ) AS REMINDER_DATE ,IF(DUE_DATE != '0000-00-00', DATE_FORMAT(DUE_DATE, '%m/%d/%Y'),'' ) AS DUE_DATE, IF(DOA_EMAIL_RECEPTION.CREATED_ON != '0000-00-00', DATE_FORMAT(DOA_EMAIL_RECEPTION.CREATED_ON, '%m/%d/%Y %r'),'' ) AS CREATED_ON,DOA_USERS.FIRST_NAME from DOA_EMAIL,DOA_EMAIL_RECEPTION, DOA_USERS WHERE DOA_EMAIL.INTERNAL_ID = '$_GET[id]' AND DOA_EMAIL_RECEPTION.PK_EMAIL = DOA_EMAIL.PK_EMAIL AND DOA_EMAIL_RECEPTION.PK_USER = '$_SESSION[PK_USER]' AND DOA_USERS.PK_USER = DOA_EMAIL.CREATED_BY ORDER BY PK_EMAIL_RECEPTION DESC");

                                                    }
                                                    $i = 0;
                                                    while (!$res->EOF) {
                                                        $PK_EMAIL = $res->fields['PK_EMAIL'];
                                                        $style = '';
                                                        if($i > 0)
                                                            $style = 'display:none;'; ?>
                                                        <div class="row">
                                                            <div class="col-md-4"><hr style="height: 2px;"></div>
                                                            <div class="col-md-4 text-center">
                                                                <h4 onclick="show_div(<?=$i?>)" style="font-weight: bold; background-color: #fff;"><span><?=$res->fields['CREATED_ON']?></span></h4>
                                                            </div>
                                                            <div class="col-md-4"><hr style="height: 2px;"></div>
                                                        </div>
                                                        <div id="content_div_<?=$i?>"  style="border:1px dashed #000; padding:5px;border-radius: 7px;<?=$style?>" >
                                                            <div class="row" >
                                                                <div class="span">
                                                                    <b>From: <?=$res->fields['FIRST_NAME']?></b>
                                                                </div>
                                                            </div>
                                                            <div class="row" >
                                                                <div class="span">
                                                                    <b>To:
                                                                        <?php $k = 0;
                                                                        $res_rep = $db_account->Execute("SELECT DOA_USERS.FIRST_NAME FROM DOA_EMAIL_RECEPTION,DOA_USERS WHERE DOA_EMAIL_RECEPTION.PK_USER = DOA_USERS.PK_USER AND PK_EMAIL = '$PK_EMAIL' ");
                                                                        while (!$res_rep->EOF) {
                                                                            if($k > 0)
                                                                                echo ", ";
                                                                            echo $res_rep->fields['FIRST_NAME'];
                                                                            $res_rep->MoveNext();
                                                                            $k++;
                                                                        } ?>
                                                                    </b>
                                                                </div>
                                                            </div>
                                                            <hr />

                                                            <div class="row">
                                                                <div class="span">
                                                                    <b>Content:</b><br>
                                                                    <?=$res->fields['CONTENT']?>
                                                                </div>
                                                            </div>
                                                            <?php $res_att = $db_account->Execute("SELECT * FROM DOA_EMAIL_ATTACHMENT WHERE PK_EMAIL = '$PK_EMAIL' AND ACTIVE = 1");
                                                            if($res_att->RecordCount() > 0){ ?>
                                                                <u>Attachments</u><br />
                                                                <?php while (!$res_att->EOF) {  ?>
                                                                    <a href="<?=$res_att->fields['LOCATION']?>" target="_blank" ><?=$res_att->fields['FILE_NAME']?></a><br />
                                                                    <?php $res_att->MoveNext();
                                                                } ?>
                                                            <?php }
                                                            ?>
                                                        </div>
                                                        <?php $i++;
                                                        $res->MoveNext();
                                                    }?>

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
</div>
<?php require_once('../includes/footer.php');?>
<!-- <script type="text/javascript" src="../js/jquery.easyui.min_mail.js"></script> -->
<script type="text/javascript">
    function show_div(id){
        jQuery(document).ready(function($) {
            $('#content_div_'+id).slideToggle(200);
        });
    }
    function star(id){
        jQuery(document).ready(function($) {
            var data  = 'id='+id;
            var value = $.ajax({
                url: "set_stared.php",
                type: "POST",
                data: data,
                async: false,
                cache: false,
                success: function (data) {
                    //alert(data)
                    document.getElementById('star_id').style.color = data;
                }
            }).responseText;
        });
    }
</script>
</body>
</html>
