<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Leads";
else
    $title = "Edit Leads";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '') {
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    $LEADS_DATA = $_POST;
    if (empty($_GET['id'])) {
        $LEADS_DATA['REMOTE_ADDRESS'] = $_SERVER['REMOTE_ADDR'];
        $LEADS_DATA['IP_ADDRESS'] = getUserIP();
        $LEADS_DATA['ACTIVE'] = 1;
        $LEADS_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $LEADS_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_LEADS', $LEADS_DATA, 'insert');
    } else {
        $LEADS_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $LEADS_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $LEADS_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_LEADS', $LEADS_DATA, 'update', " PK_LEADS =  '$_GET[id]'");
    }
    header("location:all_leads.php");
}

function getUserIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        // IP from shared internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // IP passed from proxy
        $ipArray = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipArray[0]); // first IP is the real one
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        // Nginx real IP header
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } else {
        // Default
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

if (empty($_GET['id'])) {
    $PK_LOCATION = '';
    $FIRST_NAME = '';
    $LAST_NAME = '';
    $PHONE = '';
    $EMAIL_ID = '';
    $PK_LEAD_STATUS = '';
    $DESCRIPTION = '';
    $OPPORTUNITY_SOURCE = '';
    $ACTIVE = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_LEADS` WHERE PK_LEADS = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_leads.php");
        exit;
    }
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $FIRST_NAME = $res->fields['FIRST_NAME'];
    $LAST_NAME = $res->fields['LAST_NAME'];
    $PHONE = $res->fields['PHONE'];
    $EMAIL_ID = $res->fields['EMAIL_ID'];
    $PK_LEAD_STATUS = $res->fields['PK_LEAD_STATUS'];
    $DESCRIPTION = $res->fields['DESCRIPTION'];
    $OPPORTUNITY_SOURCE = $res->fields['OPPORTUNITY_SOURCE'];
    $ACTIVE = $res->fields['ACTIVE'];
}

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
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item"><a href="all_leads.php">All Lead Status</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>

                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form class="form-material form-horizontal m-t-30" name="form1" id="form1" action="" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="PK_LEADS" id="PK_LEADS" value="<?= $_GET['id'] ?>" />

                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Location</label>
                                            <div class="col-md-12">
                                                <select class="form-control" name="PK_LOCATION" id="PK_LOCATION">
                                                    <option value="">Select Location</option>
                                                    <?php
                                                    $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                    while (!$row->EOF) { ?>
                                                        <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($row->fields['PK_LOCATION'] == $PK_LOCATION) ? "selected" : "" ?>><?= $row->fields['LOCATION_NAME'] ?></option>
                                                    <?php
                                                        $row->MoveNext();
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">First Name</label>
                                            <div class="col-md-12">
                                                <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" value="<?= $FIRST_NAME ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Last Name</label>
                                            <div class="col-md-12">
                                                <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name" value="<?= $LAST_NAME ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Phone</label>
                                            <div class="col-md-12">
                                                <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone Number" value="<?php echo $PHONE ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Email</label>
                                            <div class="col-md-12">
                                                <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" value="<?= $EMAIL_ID ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-4">
                                        <label class="form-label">Lead Status</label>
                                        <div class="form-group">
                                            <select class="form-control" name="PK_LEAD_STATUS" id="PK_LEAD_STATUS" required>
                                                <?php
                                                $row = $db->Execute("SELECT * FROM `DOA_LEAD_STATUS` WHERE ACTIVE = 1 AND `PK_ACCOUNT_MASTER` = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DISPLAY_ORDER ASC");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_LEAD_STATUS']; ?>" <?= ($row->fields['PK_LEAD_STATUS'] == $PK_LEAD_STATUS) ? 'selected' : '' ?>><?= $row->fields['LEAD_STATUS'] ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Opportunity Source</label>
                                            <div class="col-md-12">
                                                <input type="text" id="OPPORTUNITY_SOURCE" name="OPPORTUNITY_SOURCE" class="form-control" placeholder="Enter Opportunity Source" value="<?php echo $OPPORTUNITY_SOURCE ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Description</label>
                                            <div class="col-md-12">
                                                <textarea class="form-control" name="DESCRIPTION" rows="2"><?= $DESCRIPTION ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($_GET['id'])) { ?>
                                        <div class="col-4">
                                            <div class="form-group">
                                                <label class="m-r-25">Active</label>
                                                <label class="m-r-15"><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if ($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if ($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                            </div>
                                        </div>
                                    <? } ?>

                                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="margin-top: 10px;">Submit</button>
                                    <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_leads.php'">Cancel</button>
                                    <a href="javascript:;" onclick="createCustomer()" class="btn btn-success waves-effect waves-light m-r-10 text-white" style="margin-top: 10px;">Create Customer</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
</body>

<script>
    function createCustomer() {
        let PK_LEADS = $('#PK_LEADS').val();
        let PK_LOCATION = $('#PK_LOCATION').val();
        let FIRST_NAME = $('#FIRST_NAME').val();
        let LAST_NAME = $('#LAST_NAME').val();
        let PHONE = $('#PHONE').val();
        let EMAIL_ID = $('#EMAIL_ID').val();
        window.location.href = `customer.php?PK_LOCATION=${PK_LOCATION}&FIRST_NAME=${FIRST_NAME}&LAST_NAME=${LAST_NAME}&PHONE=${PHONE}&EMAIL_ID=${EMAIL_ID}&PK_LEADS=${PK_LEADS}`;
    }
</script>

</html>