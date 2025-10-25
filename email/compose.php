<?php
require_once("../global/config.php");
global $db;
global $db_account;

$title = "Compose Mail";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '') {
    header("location:../index.php");
    exit;
}

$id = empty($_GET['id']) ? '' : $_GET['id'];
$type = empty($_GET['type']) ? '' : $_GET['type'];
$mail_type = empty($_GET['mail_type']) ? '' : $_GET['mail_type'];

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'] ?? 0;

if (!empty($_POST)) {
    $RECEPTIONS          = $_POST['RECEPTION'];
    $FILE_NAMES           = $_POST['FILE_NAME'];
    $FILE_LOCATIONS      = $_POST['FILE_LOCATION'];
    $PK_EMAIL_ATTACHMENT = $_POST['PK_EMAIL_ATTACHMENT'];
    unset($_POST['RECEPTION']);
    unset($_POST['FILE_NAME']);
    unset($_POST['FILE_LOCATION']);
    unset($_POST['PK_EMAIL_ATTACHMENT']);

    if (isset($_POST['REMINDER_DATE']))
        $_POST['REMINDER_DATE'] = date("Y-m-d", strtotime($_POST['REMINDER_DATE']));

    if (isset($_POST['DUE_DATE']))
        $_POST['DUE_DATE'] = date("Y-m-d", strtotime($_POST['DUE_DATE']));

    $EMAIL = $_POST;
    if ($id == '' || $type == 'forward') {
        $EMAIL['PK_EMAIL_STATUS']      = 1;
        $EMAIL['CREATED_BY']          = $_SESSION['PK_USER'];
        $EMAIL['CREATED_ON']          = date("Y-m-d H:i");
        $EMAIL['INTERNAL_ID']          = 0;
        db_perform('DOA_EMAIL', $EMAIL, 'insert');
        $PK_EMAIL = $db->insert_ID();

        $EMAIL1['INTERNAL_ID']     = $PK_EMAIL;
        $INTERNAL_ID            = $PK_EMAIL;
        db_perform('DOA_EMAIL', $EMAIL1, 'update', " PK_EMAIL = '$PK_EMAIL' ");
    } else {
        if ($type == 'draft') {
            $PK_EMAIL = $id;
            db_perform('DOA_EMAIL', $EMAIL, 'update', " PK_EMAIL = '1' AND CREATED_BY = '$_SESSION[PK_USER]' ");
        } else {
            $PK_EMAIL = $_GET['pk'];

            $res = $db->Execute("SELECT INTERNAL_ID from DOA_EMAIL WHERE PK_EMAIL = '$PK_EMAIL' ");
            $INTERNAL_ID = $res->fields['INTERNAL_ID'];

            $EMAIL['INTERNAL_ID']         = $INTERNAL_ID;
            $EMAIL['PK_EMAIL_STATUS']      = 1;
            $EMAIL['CREATED_BY']          = $_SESSION['PK_USER'];
            $EMAIL['CREATED_ON']          = date("Y-m-d H:i");

            db_perform('DOA_EMAIL', $EMAIL, 'insert');
            die();
            $PK_EMAIL = $db->insert_ID();
        }
    }
    if (!empty($RECEPTIONS)) {
        foreach ($RECEPTIONS as $RECEPTION) {
            $res = $db->Execute("select PK_EMAIL_RECEPTION from DOA_EMAIL_RECEPTION WHERE PK_EMAIL = '$PK_EMAIL' AND PK_USER = '$RECEPTION' ");

            if ($res->RecordCount() == 0) {
                $EMAIL_RECEPTION['INTERNAL_ID'] = $INTERNAL_ID;
                $EMAIL_RECEPTION['PK_EMAIL']     = $PK_EMAIL;
                $EMAIL_RECEPTION['PK_USER']     = $RECEPTION;
                $EMAIL_RECEPTION['VIWED']         = 0;
                $EMAIL_RECEPTION['REPLY']         = 0;
                $EMAIL_RECEPTION['DELETED']     = 0;
                $EMAIL_RECEPTION['CREATED_ON']  = date("Y-m-d H:i");
                db_perform('DOA_EMAIL_RECEPTION', $EMAIL_RECEPTION, 'insert');
                $PK_EMAIL_RECEPTION_IDS[] =  $db->insert_ID();
            } else {
                $PK_EMAIL_RECEPTION_IDS[] = $res->fields['PK_EMAIL_RECEPTION'];
            }
        }
    }

    $cond = "";
    if (!empty($PK_EMAIL_RECEPTION_IDS)) {
        $cond = " AND PK_EMAIL_RECEPTION NOT IN (" . implode(",", $PK_EMAIL_RECEPTION_IDS) . ") ";
    }
    $db->Execute("DELETE from DOA_EMAIL_RECEPTION WHERE PK_EMAIL = '$PK_EMAIL' $cond ");

    $i = 0;
    if (!empty($FILE_NAMES)) {
        foreach ($FILE_NAMES as $FILE_NAME) {
            $EMAIL_ATTACHMENT['PK_EMAIL']      = $PK_EMAIL;
            $EMAIL_ATTACHMENT['FILE_NAME']      = $FILE_NAME;
            $EMAIL_ATTACHMENT['LOCATION']      = $FILE_LOCATIONS[$i];
            $EMAIL_ATTACHMENT['UPLOADED_ON'] = date("Y-m-d H:i");
            //if($PK_EMAIL_ATTACHMENT[$i] == '' || $type == 'reply'){
            db_perform('DOA_EMAIL_ATTACHMENT', $EMAIL_ATTACHMENT, 'insert');
            //}
            $i++;
        }
    }
    if ($mail_type != '') { ?>
        <script type="text/javascript">
            window.opener.close_mail_window(this);
        </script>
<?php } else {
        if ($_POST['DRAFT'] == 0)
            header("location:email.php");
        else
            header("location:email.php?type=draft");
    }
}

$replay_user_array = array(0);
$PK_EMAIL_TYPE     = '';
$SUBJECT         = '';
$CONTENT         = '';
$REMINDER_DATE     = '';
$DUE_DATE        = '';
$SUBJECT_REP_FAR = '';

if (!empty($id)) {
    $table = "";
    if ($type == 'reply' || $type == 'forward') {
        $cond  = " AND DOA_EMAIL_RECEPTION.PK_EMAIL = '$_GET[pk]' AND DOA_EMAIL.PK_EMAIL = DOA_EMAIL_RECEPTION.PK_EMAIL  ";
        $table = ",DOA_EMAIL_RECEPTION";
    } else
        $cond = " AND DOA_EMAIL.PK_EMAIL = '$_GET[id]' AND CREATED_BY = '$_SESSION[PK_USER]' ";

    $res = $db->Execute("select DOA_EMAIL.* from DOA_EMAIL $table WHERE 1=1 $cond");

    if ($type == 'reply') {
        $replay_user_arrayss = $db->Execute("select PK_USER from DOA_EMAIL_RECEPTION WHERE PK_EMAIL = '$_GET[pk]' ");
        $replay_user_array   = array_values($replay_user_arrayss->fields);
        $replay_user_array[] = $res->fields['CREATED_BY'];

        if (($key = array_search($_SESSION['PK_USER'], $replay_user_array)) !== false) {
            unset($replay_user_array[$key]);
        }
    }
    if ($res->RecordCount() == 0) {
        header("location:email.php");
        exit;
    }

    $PK_EMAIL_TYPE     = $res->fields['PK_EMAIL_TYPE'];
    $PK_EMAIL_TYPE_REP_FAR     = $res->fields['PK_EMAIL_TYPE'];
    //$SUBJECT 		= $res->fields['SUBJECT'];
    $SUBJECT_REP_FAR         = $res->fields['SUBJECT'];

    if ($type != 'reply') {
        $CONTENT         = $res->fields['CONTENT'];
        $REMINDER_DATE     = $res->fields['REMINDER_DATE'];
        $DUE_DATE        = $res->fields['DUE_DATE'];
    }

    if ($REMINDER_DATE == '0000-00-00' || $REMINDER_DATE == '')
        $REMINDER_DATE = '';
    else
        $REMINDER_DATE = date("m/d/Y", strtotime($REMINDER_DATE));

    if ($DUE_DATE == '0000-00-00' || $DUE_DATE == '')
        $DUE_DATE = '';
    else
        $DUE_DATE = date("m/d/Y", strtotime($DUE_DATE));
}

/*$mail_type = 'quote';

if(!empty($mail_type)){
    if($mail_type == 'quote') {
        $PK_EMAIL_TYPE 	= '2';
        $res = $db->Execute("select QUOTE_NO from DOA_QUOTE_MASTER WHERE PK_QUOTE_MASTER = '$_GET[e_id]' ");
        $SUBJECT = 'Quote # '.$res->fields['QUOTE_NO'].' ';
    } else if($mail_type == 'order') {
        $PK_EMAIL_TYPE = '3';
        $res = $db->Execute("select ORDER_NO from DOA_ORDER_MASTER WHERE PK_ORDER_MASTER = '$_GET[e_id]' ");
        $SUBJECT = 'Order # '.$res->fields['ORDER_NO'].' ';
    } else if($mail_type == 'shipping') {
        $PK_EMAIL_TYPE = '5';
        $res_sm = $db->Execute("SELECT SHIPPING_MASTER.*,ORDER_NO, ORDER_MASTER.PK_ORDER_MASTER from DOA_ORDER_MASTER,DOA_SHIPPING_MASTER WHERE SHIPPING_MASTER.PK_SHIPPING_MASTER = '$_GET[e_id]' AND ORDER_MASTER.PK_ORDER_MASTER = SHIPPING_MASTER.PK_ORDER_MASTER ");
        $PK_ORDER_MASTER = $res_sm->fields['PK_ORDER_MASTER'];
        $SHIPPING_NO	 = $res_sm->fields['SHIPPING_NO'];
        $ORDER_NO	 	 = $res_sm->fields['ORDER_NO'];
        $SUBJECT = 'Shipping # '.$SHIPPING_NO.' (Order # '.$ORDER_NO.') ';
    } else if($mail_type == 'Order Payment' || $mail_type == 'Invoice Payment') {
        $PK_EMAIL_TYPE = '6';
        $SUBJECT = $mail_type.' Ref # '.$_GET['e_id'];
    }
}*/

$default_selected_cus = (!empty($_GET['sel_uid'])) ? $_GET['sel_uid'] : '';
if ($default_selected_cus)
    $replay_user_array[] = $default_selected_cus;

?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <div class="container-fluid body_content">
                <div class="main">
                    <div class="main-inner">
                        <div class="container">
                            <div class="row">
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
                                            <div class="widget">
                                                <div class="widget-content">
                                                    <form method="post" action="compose.php" enctype="multipart/form-data" id="form1">
                                                        <?php if (!empty($mail_type) != '') { ?>
                                                            <input type="hidden" name="EMAIL_FOR" value="<?= 7 ?>" />
                                                            <input type="hidden" name="ID" value="<?= 1 ?>" />
                                                        <?php } ?>
                                                        <div class="container">
                                                            <div class="row">
                                                                <div class="span12">
                                                                    <h3>
                                                                        <?php if ($id == '') echo "Compose Email";
                                                                        else if ($type == 'reply') echo "Reply ";
                                                                        else if ($type == 'forward') echo "Forward ";
                                                                        else echo "Draft Email"; ?>
                                                                    </h3>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="container">
                                                            <?php if ($type == 'reply') { ?>
                                                                <div class="row">
                                                                    <div class="span2">
                                                                        <label>Subject</label>
                                                                    </div>
                                                                    <div class="span9">
                                                                        <label><?= $SUBJECT_REP_FAR ?></label>
                                                                    </div>
                                                                </div>

                                                            <?php } ?>

                                                            <div <?php if ($type == 'reply') { ?> style="display: none;" <?php } ?>>
                                                                <div class="row pt-2">
                                                                    <div class="span2 col-md-2">
                                                                        <label>Subject</label>
                                                                    </div>
                                                                    <div class="span9 col-md-10">
                                                                        <input type="text" id="SUBJECT" name="SUBJECT" value="<?= $SUBJECT_REP_FAR ?>" placeholder="" class="required-entry form-control" style="width:95%; border: 1px solid #aaaaaa;" required />
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row pt-2" style="margin-bottom:10px;">
                                                                <div class="span2 col-md-2">
                                                                    <label>Recipients/To</label>
                                                                </div>
                                                                <div class="span9 col-md-10">
                                                                    <?php if ($_SESSION['PK_ROLES'] == 4) { ?>
                                                                        <select name="RECEPTION[]" id="RECEPTION" class="form-control required-entry select2" style="width:95%" multiple required>
                                                                            <?php
                                                                            $res_type = $res_type = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER=DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES IN (3, 5) AND DOA_USERS.ACTIVE = '1' AND (DOA_USERS.IS_DELETED = 0 || DOA_USERS.IS_DELETED IS NULL) AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DOA_USERS.FIRST_NAME ASC");

                                                                            while (!$res_type->EOF) {
                                                                                $PK_USER = $res_type->fields['PK_USER'];
                                                                                $selected = '';
                                                                                if ($id != '') {
                                                                                    if (in_array($PK_USER, $replay_user_array))
                                                                                        $selected = 'selected';
                                                                                } elseif ($default_selected_cus && $default_selected_cus == $PK_USER)
                                                                                    $selected = 'selected';
                                                                            ?>
                                                                                <option value="<?= $PK_USER ?>" <?= $selected ?>><?= $res_type->fields['FIRST_NAME'] ?> <?= $res_type->fields['LAST_NAME'] ?> (<?= $res_type->fields['USER_NAME'] ?>)</option>
                                                                            <?php $res_type->MoveNext();
                                                                            } ?>
                                                                        </select>
                                                                    <?php } else { ?>
                                                                        <select name="RECEPTION[]" id="RECEPTION" class="form-control required-entry select2" style="width:95%" multiple required>
                                                                            <option value="1" <?= (in_array(1, $replay_user_array) ? 'selected' : '') ?>>Super Admin</option>
                                                                            <?php
                                                                            $res_type = $res_type = $db->Execute("select PK_USER,USER_NAME,FIRST_NAME,LAST_NAME from DOA_USERS WHERE ACTIVE = '1' AND PK_ACCOUNT_MASTER = $PK_ACCOUNT_MASTER AND PK_USER != $_SESSION[PK_USER] OR PK_USER IN (" . implode(',', $replay_user_array) . ")");

                                                                            while (!$res_type->EOF) {
                                                                                $PK_USER = $res_type->fields['PK_USER'];
                                                                                $selected = '';
                                                                                if ($id != '') {
                                                                                    if (in_array($PK_USER, $replay_user_array))
                                                                                        $selected = 'selected';
                                                                                } elseif ($default_selected_cus && $default_selected_cus == $PK_USER)
                                                                                    $selected = 'selected';
                                                                            ?>
                                                                                <option value="<?= $PK_USER ?>" <?= $selected ?>><?= $res_type->fields['FIRST_NAME'] ?> <?= $res_type->fields['LAST_NAME'] ?> (<?= $res_type->fields['USER_NAME'] ?>)</option>
                                                                            <?php $res_type->MoveNext();
                                                                            } ?>
                                                                        </select>
                                                                    <?php } ?>
                                                                </div>
                                                            </div>

                                                            <div class="row pt-2">
                                                                <div class="span2 col-md-2">
                                                                    <label>Attachments</label>
                                                                </div>
                                                                <div class="span9 col-md-10">
                                                                    <input id="FILE" type="file" name="FILE[]" onchange="ajax_upload1()" />
                                                                </div>
                                                            </div>
                                                            <div class="row pt-2">
                                                                <div class="span2">&nbsp;</div>
                                                                <div class="col-md-2"></div>
                                                                <div class="span9 col-md-10" id="attachment_files">
                                                                    <?php $i = 0;
                                                                    if ($id != '' && $type != 'reply') {
                                                                        $res_type = $db->Execute("select * from DOA_EMAIL_ATTACHMENT WHERE PK_EMAIL = '$_GET[id]' ");
                                                                        while (!$res_type->EOF) { ?>
                                                                            <div id="attach_<?= $i ?>">
                                                                                <input type="hidden" name="PK_EMAIL_ATTACHMENT[]" value="<?= $res_type->fields['PK_EMAIL_ATTACHMENT'] ?>">
                                                                                <input type="hidden" name="FILE_NAME[]" value="<?= $res_type->fields['FILE_NAME'] ?>">
                                                                                <input type="hidden" name="FILE_LOCATION[]" value="<?= $res_type->fields['LOCATION'] ?>">
                                                                                <a href="<?= $res_type->fields['LOCATION'] ?>" target="blank"><?= $res_type->fields['FILE_NAME'] ?></a>
                                                                            </div>
                                                                    <?php $i++;
                                                                            $res_type->MoveNext();
                                                                        }
                                                                    }
                                                                    $uploded_count = $i; ?>
                                                                </div>
                                                            </div>

                                                            <div class="row pt-3">
                                                                <div class="span2 col-md-2">
                                                                    <label>Content</label>
                                                                </div>
                                                                <div class="span9 col-md-10">
                                                                    <Textarea id="CONTENT" name="CONTENT" class="editor"><?= $CONTENT ?></Textarea>
                                                                </div>
                                                            </div>

                                                            <div class="row text-center pb-5" style="padding-left: 70%;">
                                                                <div class="span4">&nbsp;</div>
                                                                <div class="span4">
                                                                    <button type="submit" class="btn btn-info"><i class="fa fa-paper-plane"></i> Send</button>
                                                                    <button type="submit" onclick="save_frm(1)" class="btn btn-info"><i class="fa fa-save"></i> Save as Draft</button>
                                                                    <?php if ($mail_type != '')
                                                                        $URL = 'javascript:window.close()';
                                                                    else
                                                                        $URL = 'email.php?type=' . $type; ?>
                                                                    <button type="button" class="btn btn-info" onclick="<?= $URL ?>"><i class="fa fa-stop-circle"></i> Cancel</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <input type="hidden" name="DRAFT" id="DRAFT" value="0">
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
        </div>
    </div>
    <style>
        .progress-bar {
            border-radius: 5px;
            height: 18px !important;
        }
    </style>
    <?php require_once('../includes/footer.php'); ?>

    <link href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/4.5.6/tinymce.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js"></script>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.select2').select2();
        });
    </script>

    <script>
        jQuery(document).ready(function($) {
            $(".date").datepicker();
        });


        var uploded_count = '<?= $uploded_count ?>';

        function ajax_upload1() {
            jQuery(document).ready(function($) {
                message = 'Please Wait. Your File is file is being Uploaded';
                jQuery('body').append('<div class="delete-alert"></div>');
                $alert = jQuery('.delete-alert');
                $alert.slideDown(400);
                $alert.html(message).append('<br /><br /><br />');

                var file_data = $('#FILE').prop('files')[0];
                var form_data = new FormData();
                form_data.append('file', file_data);
                //alert(form_data);
                $.ajax({
                    url: 'ajax_upload.php', // point to server-side PHP script
                    dataType: 'text', // what to expect back from the PHP script, if anything
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_data,
                    type: 'post',
                    success: function(data) {
                        //alert(data); // display response from the PHP script, if any
                        data = data.split('||');
                        if (data[0] == 0)
                            alert(data[1]);
                        else {
                            var str = '';
                            str = '<div id="attach_' + uploded_count + '" >';
                            str += '<input type="hidden" name="PK_EMAIL_ATTACHMENT[]" value="" >';
                            str += '<input type="hidden" name="FILE_NAME[]" value="' + data[1] + '" >';
                            str += '<input type="hidden" name="FILE_LOCATION[]" value="' + data[2] + '" >';
                            str += '<a href="' + data[2] + '" target="blank" >' + data[1] + '</a>';
                            //str += 		'<a href="javascript:void(0)" onclick="delete_attachment('+uploded_count+')"><img src="../assets/images/delete.png" title="Delete Attachment"></a>';
                            str += '</div>';

                            $('#attachment_files').append(str);
                            uploded_count++;
                        }

                        $(".alert").remove();
                        $alert.slideUp(400);

                        document.getElementById('FILE').value = '';
                    }
                });
            });
        }

        jQuery(document).ready(function($) {
            tinymce.init({
                extended_valid_elements: "iframe[title,class,type,width,height,src,frameborder,allowFullScreen]",
                selector: "textarea.editor",
                browser_spellcheck: true,
                content_css: "https://fonts.googleapis.com/css?family=Open+Sans|Josefin+Slab|Arvo|Lato|Vollkorn|Abril+Fatface|Ubuntu|PT+Sans|Old+Standard+TT|Droid+Sans",
                // ===========================================
                // INCLUDE THE PLUGIN
                // ===========================================
                plugins: [
                    "advlist autolink lists charmap media anchor",
                    "searchreplace visualblocks code fullscreen",
                    "insertdatetime media table contextmenu paste textcolor emoticons"
                ],
                media_strict: false,
                // ===========================================
                // PUT PLUGIN'S BUTTON on the toolbar
                // ===========================================
                toolbar: "insertfile | bold italic underline | styleselect | fontselect | fontsizeselect | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | forecolor ",
                // ===========================================
                // SET RELATIVE_URLS to FALSE (This is required for images to display properly)
                // ===========================================
                relative_urls: false,
                height: 300,
                setup: function(ed) {
                    ed.on("click", function() {
                        //tinymce.activeEditor.execCommand('mceInsertContent', false, "some text")
                        tinymce.execCommand('mceFocus', false, 'myeditor')
                    });
                }
            });
        });

        function save_frm(val) {
            document.getElementById('DRAFT').value = val;
            //var res_form = new VarienForm1('form1');
            //var res = res_form.submit();
            //if(res == true)
            $('#form1').submit();
        }

        $('.datepicker-past').datepicker({
            changeMonth: true,
            changeYear: true,
            format: 'mm/dd/yyyy',
            maxDate: 0
        });
    </script>
    <script>
        function confirmDelete(anchor) {
            let conf = confirm("Are you sure you want to delete?");
            if (conf)
                window.location = $(anchor).data("href");
        }
    </script>




</body>

</html>