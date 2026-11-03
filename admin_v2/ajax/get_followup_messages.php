<?php
require_once('../../global/config.php');
error_reporting(E_ALL);
global $db;
global $db_account;
global $master_database;

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

$SERVICE_PROVIDER_ID = isset($_POST['SERVICE_PROVIDER_ID']) ? $_POST['SERVICE_PROVIDER_ID'] : 0;

$followup_data = $db_account->Execute("SELECT * FROM DOA_AUTOMATION_LOG WHERE IS_ARCHIVE = 0 AND (FIND_IN_SET('$SERVICE_PROVIDER_ID', LAST_CLASS_SP_ID) > 0 OR FIND_IN_SET('$SERVICE_PROVIDER_ID', LAST_ENROLLMENT_SP_ID) > 0 OR FIND_IN_SET('$SERVICE_PROVIDER_ID', STUDIO_MANAGER_ID) > 0) ORDER BY CREATED_ON DESC");

while (!$followup_data->EOF) {
    $automation_data = $db_account->Execute("SELECT * FROM DOA_AUTOMATIONS WHERE PK_AUTOMATION_ID = '" . $followup_data->fields['PK_AUTOMATION_ID'] . "'");
    $PK_AUTOMATION_LOG = $followup_data->fields['PK_AUTOMATION_LOG'];
    $title = $automation_data->fields['TITLE'];
    $trigger_type = ucwords(strtolower(str_replace('_', ' ', $automation_data->fields['TRIGGER_TYPE'])));
    $trigger_value = ucwords(strtolower(str_replace('_', ' ', $automation_data->fields['TRIGGER_VALUE'])));

    if ($followup_data->fields['TYPE'] == 'leads') {
        $PK_LEADS = $followup_data->fields['PK_VALUE'];
        $lead_data = $db->Execute("SELECT DOA_LEADS.FIRST_NAME, DOA_LEADS.LAST_NAME, DOA_LEADS.EMAIL_ID, DOA_LEADS.PHONE, DOA_LEADS.ACTIVE, DOA_LEADS.PK_LEADS FROM DOA_LEADS WHERE PK_LEADS = '$PK_LEADS'");
        $selected_customer = $lead_data->fields['FIRST_NAME'] . ' ' . $lead_data->fields['LAST_NAME'];
        $customer_phone = $lead_data->fields['PHONE'];
        $customer_email = $lead_data->fields['EMAIL_ID'];

        $profile = getProfileBadge($selected_customer);
        $profile_initials = $profile['initials'];
        $profile_color = $profile['color'];
    } else {
        $PK_USER_MASTER = $followup_data->fields['PK_USER_MASTER'];

        $customer_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$PK_USER_MASTER'");
        $PK_USER = $customer_data->fields['PK_USER'];
        $selected_customer = $customer_data->fields['NAME'];
        $customer_phone = $customer_data->fields['PHONE'];
        $customer_email = $customer_data->fields['EMAIL_ID'];

        $profile = getProfileBadge($selected_customer);
        $profile_initials = $profile['initials'];
        $profile_color = $profile['color'];
    }

    $date = $followup_data->fields['CREATED_ON'] ?>

    <div class="appointment-profile d-flex">
        <div class="d-flex align-items-center gap-3 f12 theme-text-light">
            <div class="profilename-data">
                <h6 class="mb-1"><?= $title ?></h6>
                <span class=""><?= date('l, M d', strtotime($date)) ?>
                    <?php
                    if ($followup_data->fields['TYPE'] != 'leads') {
                        if (in_array($_SESSION['PK_ROLES'], [2, 3, 11]) || ($SERVICE_PROVIDER_ID == $_SESSION['PK_USER'])) { ?>
                            <a href="javascript:;" onclick=addToInternalNote(<?= $PK_AUTOMATION_LOG ?>) title="Add to Internal Note"><i class="fa fa-file-text" style="color: blue; margin-left: 20px; font-size: 14px;"></i></a>
                        <?php }
                    } else {
                        if (in_array($_SESSION['PK_ROLES'], [2, 3, 11]) || ($SERVICE_PROVIDER_ID == $_SESSION['PK_USER'])) { ?>
                            <a href="javascript:;" onclick=markAsArchived(<?= $PK_AUTOMATION_LOG ?>) title="Mark as Archived"><i class="fa fa-archive" style="color: #f009; margin-left: 20px; font-size: 14px;"></i></a>
                    <?php }
                    } ?>
                </span>
            </div>
        </div>
    </div>
    <div>
        <span class="ms-auto f10"><?= $trigger_type ?></span>
        <?php
        if ($followup_data->fields['TYPE'] != 'leads') { ?>
            <span class="ms-auto f10">(<?= $trigger_value ?>)</span>
        <?php } ?>
    </div>
    <div class="statusareatext f12 theme-text-light mt-2">
        <ul class="list-inline mb-0 mt-1">
            <li class="list-inline-item">
                <span class="namebadge badge sp_badge badge-pill" style="background-color: <?= $profile_color ?>"><?= $profile_initials ?></span>
                <?php if ($followup_data->fields['TYPE'] == 'leads') { ?>
                    <a href="leads.php?id=<?= $PK_LEADS ?>" class="name text-decoration-underline fw-semibold" target="_blank"><?= $selected_customer ?></a>
                <?php } else { ?>
                    <a href="javascript:;" class="name text-decoration-underline fw-semibold" onclick="loadViewCustomerModal(<?= $PK_USER ?>, 0)"><?= $selected_customer ?></a>
                <?php } ?>
                <div class="f10 ms-4"><?= $customer_phone ?></div>
                <div class="f10 ms-4"><?= $customer_email ?></div>
            </li>
        </ul>
    </div>
    <hr class="my-2">

<?php
    $followup_data->MoveNext();
} ?>