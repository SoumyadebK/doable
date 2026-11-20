<?php
require_once('../../global/config.php');
global $db;
global $db_account;

$PK_USER_MASTER = $_POST['PK_USER_MASTER'];
$customer_details = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = " . $PK_USER_MASTER);
$PK_CUSTOMER_DETAILS = $customer_details->fields['PK_CUSTOMER_DETAILS'];
?>

<div class="row mb-3 mt-3">
    <div class="col-4 col-md-4">
        <div class="d-flex gap-2 align-items-center">
            <label class="mb-0" style="margin-left: 33px;">Attending With</label>
        </div>
    </div>
    <div class="col-8 col-md-8">
        <div class="form-group">
            <label style="font-weight: normal;"><input class="ATTENDING_WITH" type="checkbox" name="ATTENDING_WITH[]" value="Self" onclick="showPartnerOrFamilyMemberDiv(this)" checked> Self</label> &nbsp;&nbsp;
            <label style="font-weight: normal;"><input class="ATTENDING_WITH" type="checkbox" name="ATTENDING_WITH[]" value="Partner" onclick="showPartnerOrFamilyMemberDiv(this)"> Partner</label> &nbsp;&nbsp;
            <label style="font-weight: normal;"><input class="ATTENDING_WITH" type="checkbox" name="ATTENDING_WITH[]" value="Family Member" onclick="showPartnerOrFamilyMemberDiv(this)"> Family Member</label>

            <div class="form-group mt-2" style="display: none;" id="partner_div">
                <label for="FAMILY_MEMBER_NAME">Partner Name</label>
                <?php
                if ($customer_details->RecordCount() > 0 && $customer_details->fields['ATTENDING_WITH'] == 'With a Partner') {
                    echo '<br><label style="font-weight: normal; font-size: 13px;"><input type="checkbox" name="PARTNER_ID" value="' . $customer_details->fields['PK_CUSTOMER_DETAILS'] . '"> ' . $customer_details->fields['PARTNER_FIRST_NAME'] . ' ' . $customer_details->fields['PARTNER_LAST_NAME'] . '</label>'; ?>
                <?php
                } else {
                    echo "<p style=\"font-size: 13px;\">" . 'No partner details found.' . "</p>";
                }
                ?>
            </div>

            <div class="form-group mt-2" style="display: none;" id="family_member_div">
                <label for="FAMILY_MEMBER_NAME">Family Members</label>
                <?php
                $family_member_details = $db_account->Execute("SELECT DOA_CUSTOMER_DETAILS.*, DOA_RELATIONSHIP.RELATIONSHIP FROM DOA_CUSTOMER_DETAILS LEFT JOIN $master_database.DOA_RELATIONSHIP AS DOA_RELATIONSHIP ON DOA_CUSTOMER_DETAILS.PK_RELATIONSHIP = DOA_RELATIONSHIP.PK_RELATIONSHIP WHERE DOA_CUSTOMER_DETAILS.PK_CUSTOMER_PRIMARY = '$PK_CUSTOMER_DETAILS' AND DOA_CUSTOMER_DETAILS.IS_PRIMARY = 0");
                if ($PK_CUSTOMER_DETAILS > 0 && $family_member_details->RecordCount() > 0) {
                    while (!$family_member_details->EOF) { ?>
                        <br><label style="font-weight: normal; font-size: 13px;"><input type="checkbox" name="FAMILY_MEMBER_ID[]" value="<?php echo $family_member_details->fields['PK_CUSTOMER_DETAILS']; ?>"> <?php echo $family_member_details->fields['FIRST_NAME'] . ' ' . $family_member_details->fields['LAST_NAME'] . ' (' . $family_member_details->fields['RELATIONSHIP'] . ')'; ?></label>
                <?php
                        $family_member_details->MoveNext();
                    }
                } else {
                    echo "<p style=\"font-size: 13px;\">" . 'No family member details found.' . "</p>";
                }
                ?>
            </div>

        </div>
    </div>
</div>

<script>
    function showPartnerOrFamilyMemberDiv(param) {
        let ATTENDING_WITH = $('input[name="ATTENDING_WITH[]"]:checked').map(function() {
            return this.value;
        }).get();

        if (ATTENDING_WITH.includes('Partner')) {
            $('#partner_div').show();
        } else {
            $('#partner_div').hide();
        }

        if (ATTENDING_WITH.includes('Family Member')) {
            $('#family_member_div').show();
        } else {
            $('#family_member_div').hide();
        }
    }
</script>