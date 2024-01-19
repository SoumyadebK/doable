<?php
$package = $db_account->Execute("SELECT IS_PACKAGE FROM DOA_SERVICE_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY SERVICE_NAME");
$IS_PACKAGE = $package->fields['IS_PACKAGE'];

$ENROLLMENT_NAME = '';
$PK_LOCATION = '';
$PK_PACKAGE = '';
$TOTAL = '';
$DISCOUNT_TYPE = '';
$DISCOUNT = '';
$FINAL_AMOUNT = '';
$PK_AGREEMENT_TYPE = '';
$PK_DOCUMENT_LIBRARY = '';
$AGREEMENT_PDF_LINK = '';
$ENROLLMENT_BY_ID = $_SESSION['PK_USER'];
$MEMO = '';
$ACTIVE = '';

$PK_ENROLLMENT_BILLING = '';
$BILLING_REF = '';
$BILLING_DATE = '';
$DOWN_PAYMENT = 0;
$BALANCE_PAYABLE = '';
$PAYMENT_METHOD = '';
$PAYMENT_TERM = '';
$NUMBER_OF_PAYMENT = '';
$FIRST_DUE_DATE = '';
$INSTALLMENT_AMOUNT = '';

$PK_ENROLLMENT_PAYMENT = '';
$PK_PAYMENT_TYPE = '';
$AMOUNT = '';
$NAME = '';
$CARD_NUMBER = '';
$SECURITY_CODE = '';
$EXPIRATION_DATE = '';
$CHECK_NUMBER = '';
$CHECK_DATE = '';
$NOTE = '';
?>

<!DOCTYPE html>
<html lang="en">
<style>
    #enrollmentModel {
        z-index: 1;
    }

    #myModal {
        z-index: 2; // This will come above popup1
    }
</style>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet"/>
<div id="enrollmentModel" class="modal">
    <!-- Modal content -->
    <div class="modal-content" style="margin-top: 2%; width: 100%;">
        <span class="close close_enrollment_model" style="margin-left: 96%;">&times;</span>
        <div class="card">
            <div class="card-body">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs" role="tablist">
                    <li class="active"> <a class="nav-link active" data-bs-toggle="tab" id="enrollment_tab_link" href="#enrollment_tab" role="tab"><span class="hidden-sm-up"><i class="ti-pencil-alt"></i></span> <span class="hidden-xs-down">Enrollment</span></a> </li>
                    <li> <a class="nav-link" data-bs-toggle="tab" id="billing_tab_link" href="#billing_tab" role="tab" onclick="goToPaymentTab()"><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Billing</span></a> </li>
                </ul>


                <!-- Enrollment Tab panes -->
                <div class="tab-content tabcontent-border">
                    <div class="tab-pane active" id="enrollment_tab" role="tabpanel">
                        <form id="enrollment_tab_form">
                            <input type="hidden" name="FUNCTION_NAME" value="saveEnrollmentData">
                            <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER" value="<?=(empty($_GET['enrollment_id']))?'':$_GET['enrollment_id']?>">
                            <div class="p-20">
                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Customer<span class="text-danger">*</span></label><br>
                                            <select required name="PK_USER_MASTER" id="PK_USER_MASTER_MODEL" onchange="selectThisCustomerLocation(this);">
                                                <option value="">Select Customer</option>
                                                <?php
                                                $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER, DOA_USER_MASTER.PRIMARY_LOCATION_ID FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USERS.ACTIVE=1 AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY DOA_USERS.FIRST_NAME");
                                                while (!$row->EOF) { ($PK_USER_MASTER == $row->fields['PK_USER_MASTER'])?$PK_LOCATION=$row->fields['PRIMARY_LOCATION_ID']:0;?>
                                                    <option value="<?php echo $row->fields['PK_USER_MASTER'];?>" data-location_id="<?=$row->fields['PRIMARY_LOCATION_ID']?>" data-customer_name="<?=$row->fields['NAME']?>" <?=($PK_USER_MASTER == $row->fields['PK_USER_MASTER'])?'selected':''?>><?=$row->fields['NAME'].' ('.$row->fields['PHONE'].')'.' ('.$row->fields['EMAIL_ID'].')'?></option>
                                                    <?php $row->MoveNext(); } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Location<span class="text-danger">*</span></label>
                                            <select class="form-control" required name="PK_LOCATION" id="PK_LOCATION">
                                                <option value="">Select Location</option>
                                                <?php
                                                $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY LOCATION_NAME");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=($PK_LOCATION == $row->fields['PK_LOCATION'])?'selected':''?>><?=$row->fields['LOCATION_NAME']?></option>
                                                    <?php $row->MoveNext(); } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Enrollment Name</label>
                                            <input type="text" id="ENROLLMENT_NAME" name="ENROLLMENT_NAME" class="form-control" placeholder="Enter Enrollment Name" value="<?=$ENROLLMENT_NAME?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label class="form-label">Packages</label>
                                            <select class="form-control PK_PACKAGE" name="PK_PACKAGE" id="PK_PACKAGE" onchange="selectThisPackage(this)">
                                                <option>Select</option>
                                                <?php
                                                $row = $db_account->Execute("SELECT DOA_PACKAGE.PK_PACKAGE, DOA_PACKAGE.PACKAGE_NAME FROM DOA_PACKAGE WHERE ACTIVE = 1 ORDER BY PACKAGE_NAME");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_PACKAGE'];?>" <?=($row->fields['PK_PACKAGE'] == $PK_PACKAGE)?'selected':''?>><?=$row->fields['PACKAGE_NAME']?></option>
                                                    <?php $row->MoveNext(); } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label class="form-label">Services</label>
                                            </div>
                                        </div>
                                        <div class="col-1">
                                            <div class="form-group">
                                                <label class="form-label">Service Codes</label>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label class="form-label">Service Details</label>
                                            </div>
                                        </div>
                                        <div class="col-1 session_div">
                                            <div class="form-group">
                                                <label class="form-label">Number of Sessions</label>
                                            </div>
                                        </div>
                                        <div class="col-1 session_div">
                                            <div class="form-group">
                                                <label class="form-label">Price Per Sessions</label>
                                            </div>
                                        </div>
                                        <div class="col-1 frequency_div" style="display: none; text-align: center;">
                                            <div class="form-group">
                                                <label class="form-label">Frequency</label>
                                            </div>
                                        </div>
                                        <div class="col-1" style="width: 11%;">
                                            <div class="form-group">
                                                <label class="form-label">Total</label>
                                            </div>
                                        </div>
                                        <div class="col-1" style="text-align: center">
                                            <div class="form-group">
                                                <label class="form-label">Discount Type</label>
                                            </div>
                                        </div>
                                        <div class="col-1" style=" text-align: center">
                                            <div class="form-group">
                                                <label class="form-label">Discount</label>
                                            </div>
                                        </div>
                                        <div class="col-1" style="text-align: center">
                                            <div class="form-group">
                                                <label class="form-label">Final Amount</label>
                                            </div>
                                        </div>
                                    </div>

                                    <?php
                                    $PK_SERVICE_CLASS = 0;
                                    if(!empty($_GET['enrollment_id'])) {
                                        $enrollment_service_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = '$_GET[enrollment_id]'");

                                        while (!$enrollment_service_data->EOF) {
                                            $service_class = $db_account->Execute("SELECT PK_SERVICE_CLASS FROM DOA_SERVICE_MASTER WHERE PK_SERVICE_MASTER = ".$enrollment_service_data->fields['PK_SERVICE_MASTER']);
                                            $PK_SERVICE_CLASS = $service_class->fields['PK_SERVICE_CLASS'];
                                            ?>
                                            <div class="row">
                                                <div class="col-2">
                                                    <div class="form-group">
                                                        <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                            <option>Select</option>
                                                            <?php
                                                            $row = $db_account->Execute("SELECT PK_SERVICE_MASTER, SERVICE_NAME, PK_SERVICE_CLASS FROM DOA_SERVICE_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY SERVICE_NAME");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" data-service_class="<?=$row->fields['PK_SERVICE_CLASS']?>" data-service_code="<?=$enrollment_service_data->fields['PK_SERVICE_CODE']?>" data-is_package="<?=$row->fields['IS_PACKAGE']?>" <?=($row->fields['PK_SERVICE_MASTER'] == $enrollment_service_data->fields['PK_SERVICE_MASTER'])?'selected':''?>><?=$row->fields['SERVICE_NAME']?></option>
                                                                <?php $row->MoveNext(); } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-1">
                                                    <div class="form-group">
                                                        <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                            <option value="">Select</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT $account_database.DOA_SERVICE_CODE.*, $master_database.DOA_FREQUENCY.FREQUENCY FROM $account_database.DOA_SERVICE_CODE LEFT JOIN $master_database.DOA_FREQUENCY ON $account_database.DOA_SERVICE_CODE.PK_FREQUENCY = $master_database.DOA_FREQUENCY.PK_FREQUENCY WHERE $account_database.DOA_SERVICE_CODE.PK_SERVICE_MASTER = ".$enrollment_service_data->fields['PK_SERVICE_MASTER']);
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_SERVICE_CODE'];?>" data-service_details="<?=$row->fields['DESCRIPTION']?>" data-frequency="<?=$row->fields['FREQUENCY']?>" data-price="<?=$row->fields['PRICE']?>" <?=($row->fields['PK_SERVICE_CODE'] == $enrollment_service_data->fields['PK_SERVICE_CODE'])?'selected':''?>><?=$row->fields['SERVICE_CODE']?></option>
                                                                <?php $row->MoveNext(); } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <?php if($PK_SERVICE_CLASS == 1){ ?>
                                                    <div class="col-1">
                                                        <div class="form-group">
                                                            <input type="text" class="form-control FREQUENCY" name="FREQUENCY[]" value="<?=$enrollment_service_data->fields['FREQUENCY']?>" readonly>
                                                        </div>
                                                    </div>
                                                <?php }elseif($PK_SERVICE_CLASS == 2){ ?>
                                                    <div class="col-2">
                                                        <div class="form-group">
                                                            <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" value="<?=$enrollment_service_data->fields['SERVICE_DETAILS']?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-1">
                                                        <div class="form-group">
                                                            <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" value="<?=$enrollment_service_data->fields['NUMBER_OF_SESSION']?>" onkeyup="calculateServiceTotal(this)">
                                                        </div>
                                                    </div>
                                                <?php } ?>

                                                <div class="col-1">
                                                    <div class="form-group">
                                                        <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" value="<?=$enrollment_service_data->fields['PRICE_PER_SESSION']?>" onkeyup="calculateServiceTotal(this);">
                                                    </div>
                                                </div>
                                                <div class="col-1" style="width: 11%;">
                                                    <div class="form-group">
                                                        <input type="text" class="form-control TOTAL" value="<?=$enrollment_service_data->fields['TOTAL']?>" name="TOTAL[]">
                                                    </div>
                                                </div>
                                                <div class="col-1 discount_div">
                                                    <div class="form-group">
                                                        <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateDiscount(this)">
                                                            <option value="">Select</option>
                                                            <option value="1" <?=($enrollment_service_data->fields['DISCOUNT_TYPE'] == 1)?'selected':''?>>Fixed</option>
                                                            <option value="2" <?=($enrollment_service_data->fields['DISCOUNT_TYPE'] == 2)?'selected':''?>>Percent</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-1 discount_div" style="text-align: right;">
                                                    <div class="form-group">
                                                        <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" value="<?=$enrollment_service_data->fields['DISCOUNT']?>" onkeyup="calculateDiscount(this)">
                                                    </div>
                                                </div>
                                                <div class="col-1 final_div" style="text-align: right;">
                                                    <div class="form-group">
                                                        <input type="text" class="form-control FINAL_AMOUNT" name="FINAL_AMOUNT[]" value="<?=$enrollment_service_data->fields['FINAL_AMOUNT']?>" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-1" style="width: 5%;">
                                                    <div class="form-group">
                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php $enrollment_service_data->MoveNext(); } ?>
                                    <?php } else { ?>
                                        <div class="row">
                                            <div class="col-2 service_name">
                                                <div class="form-group">
                                                    <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                        <option>Select</option>
                                                        <?php
                                                        $row = $db_account->Execute("SELECT PK_SERVICE_MASTER, SERVICE_NAME, PK_SERVICE_CLASS, IS_PACKAGE FROM DOA_SERVICE_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY SERVICE_NAME");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" data-service_class="<?=$row->fields['PK_SERVICE_CLASS']?>" data-is_package="<?=$row->fields['IS_PACKAGE']?>"><?=$row->fields['SERVICE_NAME']?></option>
                                                            <?php $row->MoveNext(); } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-1 service_div">
                                                <div class="form-group">
                                                    <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                        <option value="">Select</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-2 service_div">
                                                <div class="form-group">
                                                    <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" >
                                                </div>
                                            </div>
                                            <div class="col-1 frequency_div" style="display: none;">
                                                <div class="form-group">
                                                    <input type="text" class="form-control FREQUENCY" name="FREQUENCY[]" readonly>
                                                </div>
                                            </div>
                                            <div class="col-1 session_div service_div">
                                                <div class="form-group">
                                                    <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)">
                                                </div>
                                            </div>
                                            <div class="col-1 session_div service_div">
                                                <div class="form-group">
                                                    <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);">
                                                </div>
                                            </div>
                                            <div class="col-1 service_div" style="width: 11%;">
                                                <div class="form-group">
                                                    <input type="text" class="form-control TOTAL" name="TOTAL[]">
                                                </div>
                                            </div>
                                            <div class="col-1 discount_div">
                                                <div class="form-group">
                                                    <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateDiscount(this)">
                                                        <option value="">Select</option>
                                                        <option value="1" <?=($DISCOUNT_TYPE == 1)?'selected':''?>>Fixed</option>
                                                        <option value="2" <?=($DISCOUNT_TYPE == 2)?'selected':''?>>Percent</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-1 discount_div">
                                                <div class="form-group">
                                                    <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" value="<?=$DISCOUNT?>" onkeyup="calculateDiscount(this)">
                                                </div>
                                            </div>
                                            <div class="col-1 final_div" style="text-align: right;">
                                                <div class="form-group">
                                                    <input type="text" class="form-control FINAL_AMOUNT" name="FINAL_AMOUNT[]" value="<?=$FINAL_AMOUNT?>" readonly>
                                                </div>
                                            </div>
                                            <div class="col-1 service_div" style="width: 5%; display:none">
                                                <div class="form-group">
                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>

                                <div id="append_service_div" style="margin-top: 0px">

                                </div>

                                <div class="row" id="add_more">
                                    <div class="col-12">
                                        <div class="form-group" style="float: right;">
                                            <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="addMoreServices();">Add More</a>
                                        </div>
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Agreement Type<span class="text-danger">*</span></label>
                                            <select class="form-control" required name="PK_AGREEMENT_TYPE" id="PK_AGREEMENT_TYPE">
                                                <option value="">Select Agreement Type</option>
                                                <?php
                                                $row = $db->Execute("SELECT PK_AGREEMENT_TYPE, AGREEMENT_TYPE FROM DOA_AGREEMENT_TYPE WHERE ACTIVE = 1 ORDER BY PK_AGREEMENT_TYPE");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_AGREEMENT_TYPE'];?>" <?=($PK_AGREEMENT_TYPE == $row->fields['PK_AGREEMENT_TYPE'])?'selected':''?>><?=$row->fields['AGREEMENT_TYPE']?></option>
                                                    <?php $row->MoveNext(); } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Agreement Template<span class="text-danger">*</span></label>
                                            <select class="form-control" required name="PK_DOCUMENT_LIBRARY" id="PK_DOCUMENT_LIBRARY">
                                                <option value="">Select Agreement Template</option>
                                                <?php
                                                $row = $db_account->Execute("SELECT PK_DOCUMENT_LIBRARY, DOCUMENT_NAME FROM DOA_DOCUMENT_LIBRARY WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY PK_DOCUMENT_LIBRARY");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_DOCUMENT_LIBRARY'];?>" <?=($PK_DOCUMENT_LIBRARY == $row->fields['PK_DOCUMENT_LIBRARY'])?'selected':''?>><?=$row->fields['DOCUMENT_NAME']?></option>
                                                    <?php $row->MoveNext(); } ?>
                                            </select>
                                            <?php if ($AGREEMENT_PDF_LINK != '' && $AGREEMENT_PDF_LINK != null) { ?>
                                                <a href="../uploads/enrollment_pdf/<?=$AGREEMENT_PDF_LINK?>" target="_blank">View Agreement</a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Enrollment By<span class="text-danger">*</span></label>
                                            <select class="form-control" required name="ENROLLMENT_BY_ID" id="ENROLLMENT_BY_ID">
                                                <option value="">Select</option>
                                                <?php
                                                $row = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES IN (2,3,5,6,7,8) AND DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY FIRST_NAME");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_USER'];?>" <?=($ENROLLMENT_BY_ID == $row->fields['PK_USER'])?'selected':''?>><?=$row->fields['NAME']?></option>
                                                    <?php $row->MoveNext(); } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Memo</label>
                                            <textarea class="form-control" name="MEMO" rows="3"><?=$MEMO?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Continue</button>
                                    <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!--Billing Tab-->
                    <div class="tab-pane" id="billing_tab" role="tabpanel" style="pointer-events: <?=($PK_ENROLLMENT_BILLING>0)?'none':''?>; opacity: <?=($PK_ENROLLMENT_BILLING>0)?'60%':''?>">
                        <div class="card">
                            <div class="card-body">
                                <form id="billing_form">
                                    <input type="hidden" name="FUNCTION_NAME" value="saveEnrollmentBillingData">
                                    <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER" value="<?=(empty($_GET['enrollment_id']))?'':$_GET['enrollment_id']?>">
                                    <input type="hidden" name="PK_ENROLLMENT_BILLING" class="PK_ENROLLMENT_BILLING" value="<?=$PK_ENROLLMENT_BILLING?>">
                                    <input type="hidden" name="PK_SERVICE_CLASS" class="PK_SERVICE_CLASS" value="<?=$PK_SERVICE_CLASS?>">
                                    <div class="p-20">
                                        <div class="row" id="payment_tab_div">
                                            <!--Data coming from ajax-->
                                        </div>
                                        <div class="row" style="margin-top: -50px;">
                                            <h4><b>Payment Plans</b></h4>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Billing Ref #</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="BILLING_REF" id="BILLING_REF" class="form-control" value="<?=$BILLING_REF?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Billing Date</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="BILLING_DATE" id="BILLING_DATE" value="<?=($BILLING_DATE == '')?date('m/d/Y'):date('m/d/Y', strtotime($BILLING_DATE))?>" class="form-control datepicker-normal">
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="row frequency_div">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Payment Method</label>
                                                        <div class="col-md-12">
                                                            <div class="row">
                                                                <div class="col-md-3">
                                                                    <label><input type="radio" class="form-check-inline PAYMENT_METHOD" name="PAYMENT_METHOD" value="One Time" <?=($PAYMENT_METHOD == 'One Time')?'checked':''?>>One Time</label>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label><input type="radio" class="form-check-inline PAYMENT_METHOD" name="PAYMENT_METHOD" value="Payment Plans" <?=($PAYMENT_METHOD == 'Payment Plans')?'checked':''?>>Payment Plans</label>
                                                                </div>
                                                                <div class="col-md-5">
                                                                    <label><input type="radio" class="form-check-inline PAYMENT_METHOD" name="PAYMENT_METHOD" value="Flexible Payments" <?=($PAYMENT_METHOD == 'Flexible Payments')?'checked':''?>>Flexible Payments</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Amount</label>
                                                        <div class="col-md-12">
                                                            <input type="text" name="MEMBERSHIP_PAYMENT_AMOUNT" id="MEMBERSHIP_PAYMENT_AMOUNT" value="<?=$INSTALLMENT_AMOUNT?>" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row payment_method_div" id="payment_plans_div" style="display: <?=($PAYMENT_METHOD == 'Payment Plans')?'':'none'?>;">
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">Payment Term</label>
                                                        <div class="col-md-12">
                                                            <select class="form-control" name="PAYMENT_TERM" id="PAYMENT_TERM">
                                                                <option value="">Select</option>
                                                                <option value="Monthly" <?=($PAYMENT_TERM == 'Monthly')?'selected':''?>>Monthly</option>
                                                                <option value="Quarterly" <?=($PAYMENT_TERM == 'Quarterly')?'selected':''?>>Quarterly</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">Number of Payments</label>
                                                        <div class="col-md-12">
                                                            <input type="text" name="NUMBER_OF_PAYMENT" id="NUMBER_OF_PAYMENT" value="<?=$NUMBER_OF_PAYMENT?>" class="form-control" onkeyup="calculatePaymentPlans();">
                                                        </div>
                                                        <p id="number_of_payment_error" style="color: red; display: none; font-size: 10px;">This value should be a whole number. Please correct</p>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">First Payment Date</label>
                                                        <div class="col-md-12">
                                                            <input type="text" name="FIRST_DUE_DATE" id="FIRST_DUE_DATE" value="<?=($FIRST_DUE_DATE)?date('m/d/Y', strtotime($FIRST_DUE_DATE)):''?>" class="form-control datepicker-future">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">Installment Amount</label>
                                                        <div class="col-md-12">
                                                            <input type="text" name="INSTALLMENT_AMOUNT" id="INSTALLMENT_AMOUNT" value="<?=$INSTALLMENT_AMOUNT?>" class="form-control" onkeyup="calculateNumberOfPayment(this)">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row payment_method_div" id="flexible_plans_div" style="display: <?=($PAYMENT_METHOD == 'Flexible Payments')?'':'none'?>">
                                                <div class="row">
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label">Payment Date</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label">Amount</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-3" style="margin-top: -30px;">
                                                        <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="addMorePayments();">Add More</a>
                                                    </div>
                                                </div>
                                                <?php
                                                if(!empty($_GET['enrollment_id'])) {
                                                    $flexible_payment_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_LEDGER WHERE PK_ENROLLMENT_MASTER = '$_GET[enrollment_id]'");
                                                    while (!$flexible_payment_data->EOF) { ?>
                                                        <div class="row">
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="FLEXIBLE_PAYMENT_DATE[]" class="form-control datepicker-future" value="<?=($flexible_payment_data->fields['DUE_DATE'])?date('m/d/Y', strtotime($flexible_payment_data->fields['DUE_DATE'])):''?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="FLEXIBLE_PAYMENT_AMOUNT[]" class="form-control FLEXIBLE_PAYMENT_AMOUNT" value="<?=$flexible_payment_data->fields['BALANCE']?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-3" style="padding-top: 5px;">
                                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                            </div>
                                                        </div>
                                                        <?php $flexible_payment_data->MoveNext(); } ?>
                                                <?php } else { ?>
                                                    <div class="row">
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <div class="col-md-12">
                                                                    <input type="text" name="FLEXIBLE_PAYMENT_DATE[]" class="form-control datepicker-future">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <div class="col-md-12">
                                                                    <input type="text" name="FLEXIBLE_PAYMENT_AMOUNT[]" class="form-control FLEXIBLE_PAYMENT_AMOUNT">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-3" style="padding-top: 5px;">
                                                            <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>

                                            <div class="row session_div">
                                                <div class="col-6" id="first_payment_date_div">
                                                    <div class="form-group">
                                                        <label class="form-label">First Payment Date</label>
                                                        <div class="col-md-12">
                                                            <input type="text" name="MEMBERSHIP_PAYMENT_DATE" id="MEMBERSHIP_PAYMENT_DATE" value="<?=($FIRST_DUE_DATE)?date('m/d/Y', strtotime($FIRST_DUE_DATE)):''?>" class="form-control datepicker-future">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3" id="down_payment_div" style="display: <?=($PAYMENT_METHOD == 'One Time')?'none':''?>">
                                                    <div class="form-group">
                                                        <label class="form-label">Down Payment</label>
                                                        <div class="col-md-12">
                                                            <input type="text" name="DOWN_PAYMENT" id="DOWN_PAYMENT" value="<?=$DOWN_PAYMENT?>" class="form-control" onkeyup="calculatePayment()">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">Balance Payable</label>
                                                        <div class="col-md-12">
                                                            <input type="text" name="BALANCE_PAYABLE" id="BALANCE_PAYABLE" value="<?=$BALANCE_PAYABLE?>" class="form-control" value="0.00" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>


                                        <?php if($PK_ENROLLMENT_BILLING == '') {?>
                                            <div class="form-group">
                                                <a class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: none;" onclick="$('#enrollment_tab_link')[0].click();">Back</a>
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: none;">Save</button>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!--Payment Model-->
                    <?php include('payment.php'); ?>

                </div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>
<script>
    // Get the modal
    var modal = document.getElementById("myModal");

    // Get the <span> element that closes the modal
    var modal_span = document.getElementsByClassName("close")[0];

    // When the user clicks the button, open the modal
    function openModel() {
        $('#PK_PAYMENT_TYPE').val('');
        $('.payment_type_div').slideUp();
        $('#wallet_balance_div').slideUp();
        $('#remaining_amount_div').slideUp();
        $('#PK_PAYMENT_TYPE_REMAINING').prop('required', false);
        modal.style.display = "block";
    }

    // When the user clicks on <span> (x), close the modal
    modal_span.onclick = function() {
        modal.style.display = "none";
    }

    // When the user clicks anywhere out side of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    $(document).keydown(function(e) {
        // ESCAPE key pressed
        if (e.keyCode == 27) {
            modal.style.display = "none";
        }
    });
</script>
<script>
    let PK_ENROLLMENT_MASTER = parseInt(<?=empty($_GET['enrollment_id'])?0:$_GET['enrollment_id']?>);
    var PK_SERVICE_CLASS = parseInt(<?=empty($PK_SERVICE_CLASS)?0:$PK_SERVICE_CLASS?>);
    if (PK_ENROLLMENT_MASTER > 0){
        selectThisService($('.PK_SERVICE_MASTER'));
    }

    $('#PK_USER_MASTER_MODEL').SumoSelect({placeholder: 'Select Customer', search: true, searchText: 'Search...'});

    $('.datepicker-future').datepicker({
        format: 'mm/dd/yyyy',
        minDate: 0
    });

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });


    function selectThisCustomerLocation(param){
        let location_id = $(param).find(':selected').data('location_id');
        $('#PK_LOCATION').val(location_id);
    }

    function addMoreServices() {
        $('#append_service_div').append(`<div class="row">
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                        <option>Select</option>
                                                        <?php
        $row = $db_account->Execute("SELECT PK_SERVICE_MASTER, SERVICE_NAME, PK_SERVICE_CLASS FROM DOA_SERVICE_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY SERVICE_NAME");
        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" data-service_class="<?=$row->fields['PK_SERVICE_CLASS']?>"><?=$row->fields['SERVICE_NAME']?></option>
                                                        <?php $row->MoveNext(); } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                        <option value="">Select</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" >
                                                </div>
                                            </div>
                                            <div class="col-1 frequency_div" style="display: none;">
                                                <div class="form-group">
                                                    <input type="text" class="form-control FREQUENCY" name="FREQUENCY[]" readonly>
                                                </div>
                                            </div>
                                            <div class="col-1 session_div">
                                                <div class="form-group">
                                                    <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)">
                                                </div>
                                            </div>
                                            <div class="col-1 session_div">
                                                <div class="form-group">
                                                    <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);">
                                                </div>
                                            </div>
                                            <div class="col-1" style="width: 11%;">
                                                <div class="form-group">
                                                    <input type="text" class="form-control TOTAL" name="TOTAL[]">
                                                </div>
                                            </div>
                                            <div class="col-1" style="text-align: right">
                                            <div class="form-group">
                                                                    <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateDiscount(this)">
                                                                        <option value="">Select</option>
                                                                        <option value="1">Fixed</option>
                                                                        <option value="2">Percent</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-1" style="text-align: right">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" onkeyup="calculateDiscount(this)">
                                                                </div>
                                                            </div>
                                                            <div class="col-1" style="text-align: right">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control FINAL_AMOUNT" name="FINAL_AMOUNT[]" readonly>
                                                                </div>
                                                            </div>
                                            <div class="col-1" style="width: 5%;">
                                                <div class="form-group">
                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                </div>
                                            </div>
                                        </div>`);
    }

    function removeThis(param) {
        $(param).closest('.row').remove();
    }

    function addMorePayments(){
        let total_bill = parseFloat(($('#total_bill').val())?$('#total_bill').val():0);
        let down_payment = parseFloat(($('#DOWN_PAYMENT').val())?$('#DOWN_PAYMENT').val():0);
        let total_flexible_payment = 0;
        $('.FLEXIBLE_PAYMENT_AMOUNT').each(function () {
            total_flexible_payment += parseFloat($(this).val());
        });
        if ((total_flexible_payment+down_payment) < total_bill) {
            $('#flexible_plans_div').append(`<div class="row">
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        <input type="text" name="FLEXIBLE_PAYMENT_DATE[]" class="form-control datepicker-future">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        <input type="text" name="FLEXIBLE_PAYMENT_AMOUNT[]" class="form-control FLEXIBLE_PAYMENT_AMOUNT">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3" style="padding-top: 5px;">
                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                            </div>
                                        </div>`);
            $('.datepicker-future').datepicker({
                format: 'mm/dd/yyyy',
                minDate: 0
            });
        }else {
            alert('Total Bill Amount Exceed');
        }
    }

    function selectThisService(param) {
        let PK_SERVICE_MASTER = $(param).val();
        PK_SERVICE_CLASS = $(param).find(':selected').data('service_class');
        let IS_PACKAGE = $(param).find(':selected').data('is_package');
        let SERVICE_CODE = ($(param).find(':selected').data('service_code'))?$(param).find(':selected').data('service_code'):0;
        $('.PK_SERVICE_CLASS').val(PK_SERVICE_CLASS);
        if (PK_SERVICE_CLASS === 1){
            $('.session_div').hide();
            $('.frequency_div').show();
        }else {
            if (PK_SERVICE_CLASS === 2){
                $('.session_div').show();
                $('.frequency_div').hide();
            }
        }

        if (SERVICE_CODE == 0) {
            $.ajax({
                url: "ajax/get_service_codes.php",
                type: "POST",
                data: {PK_SERVICE_MASTER: PK_SERVICE_MASTER, SERVICE_CODE: SERVICE_CODE},
                async: false,
                cache: false,
                success: function (result) {
                    $(param).closest('.row').find('.PK_SERVICE_CODE').empty();
                    $(param).closest('.row').find('.PK_SERVICE_CODE').append(result);
                }
            });
        }

        //$('#package_services').html('');

        /*if (IS_PACKAGE == 1){
            $.ajax({
                url: "ajax/get_package_service_codes.php",
                type: "POST",
                data: {PK_SERVICE_MASTER: PK_SERVICE_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('.service_div').hide();
                    $('#add_more').hide();
                    $('#package_services').html(result);
                }
            });
        } else {
            $('.service_div').show();
            $('#add_more').show();
        }*/
    }

    function selectThisPackage(param) {
        let PK_PACKAGE = $(param).val();
        if (PK_PACKAGE != 'Select') {
            $.ajax({
                url: "ajax/get_packages.php",
                type: "POST",
                data: {PK_PACKAGE: PK_PACKAGE},
                async: false,
                cache: false,
                success: function (result) {
                    console.log(result)
                    $('.service_name').remove();
                    $('.service_div').remove();
                    $('.discount_div').remove();
                    $('.final_div').remove();
                    $('#add_more').show();
                    $('#append_service_div').html(result);
                }
            });
        }

    }

    function selectThisServiceCode(param) {
        let service_details = $(param).find(':selected').data('service_details');
        let price = $(param).find(':selected').data('price');
        let frequency = $(param).find(':selected').data('frequency');
        $(param).closest('.row').find('.SERVICE_DETAILS').val(service_details);
        $(param).closest('.row').find('.PRICE_PER_SESSION').val(price);
        $(param).closest('.row').find('.FREQUENCY').val(frequency);
        PK_SERVICE_CLASS = $(param).closest('.row').find('.PK_SERVICE_MASTER').find(':selected').data('service_class');
        if (PK_SERVICE_CLASS === 1) {
            $(param).closest('.row').find('.NUMBER_OF_SESSION').val(1);
        }
        calculateServiceTotal(param);
    }

    function calculateServiceTotal(param) {
        let number_of_session = $(param).closest('.row').find('.NUMBER_OF_SESSION').val();
        number_of_session = (number_of_session)?number_of_session:0;
        let service_price = $(param).closest('.row').find('.PRICE_PER_SESSION').val();
        service_price = (service_price)?service_price:0;
        $(param).closest('.row').find('.TOTAL').val(parseFloat(parseFloat(service_price)*parseFloat(number_of_session)).toFixed(2));
    }

    $(document).on('click', '#cancel_button', function () {
        window.location.href='customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER;
    });

    $(document).on('submit', '#enrollment_tab_form', function (event) {
        event.preventDefault();
        let form_data = $('#enrollment_tab_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            dataType: 'json',
            success:function (data) {
                $('.PK_ENROLLMENT_MASTER').val(data.PK_ENROLLMENT_MASTER);
                $('#MEMBERSHIP_PAYMENT_AMOUNT').val(parseFloat(data.TOTAL_AMOUNT).toFixed(2));
                $('#billing_tab_link')[0].click();
            }
        });
    });

    function goToPaymentTab() {
        let PK_ENROLLMENT_MASTER = $('.PK_ENROLLMENT_MASTER').val();
        if (PK_ENROLLMENT_MASTER) {
            $.ajax({
                url: "ajax/show_payment_tab.php",
                type: 'POST',
                data: {PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER, PK_SERVICE_CLASS:PK_SERVICE_CLASS},
                success: function (data) {
                    $('#payment_tab_div').html(data);
                    calculatePayment();
                }
            });
        }else{
            alert('Please fill up the enrollment form first');
            $('#enrollment_tab_link')[0].click();
        }
    }

    function goToLedgerTab() {
        let PK_ENROLLMENT_MASTER = $('.PK_ENROLLMENT_MASTER').val();
        if (!PK_ENROLLMENT_MASTER) {
            alert('Please fill up the enrollment form first');
            $('#enrollment_tab_link')[0].click();
        }
    }

    function calculateDiscount(param) {
        let DISCOUNT = $(param).closest('.row').find('.DISCOUNT').val();
        let DISCOUNT_TYPE = $(param).closest('.row').find('.DISCOUNT_TYPE').val();
        let TOTAL = $(param).closest('.row').find('.TOTAL').val();

        if (DISCOUNT_TYPE == 1){
            let FINAL_AMOUNT = parseFloat(TOTAL-DISCOUNT);
            $(param).closest('.row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));
        } else {
            if (DISCOUNT_TYPE == 2) {
                let FINAL_AMOUNT = parseFloat(TOTAL - (TOTAL * (DISCOUNT / 100)));
                $(param).closest('.row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));
            }
        }
        let TOTAL_AMOUNT = 0;
        $('.FINAL_AMOUNT').each(function () {
            TOTAL_AMOUNT += parseFloat($(this).val());
        });
        $('#total_bill').val(parseFloat(TOTAL_AMOUNT).toFixed(2));
        $('#BALANCE_PAYABLE').val(parseFloat(TOTAL_AMOUNT).toFixed(2));
    }

    function calculatePayment() {
        let total_bill = parseFloat(($('#total_bill').val())?$('#total_bill').val():0);
        let down_payment = parseFloat(($('#DOWN_PAYMENT').val())?$('#DOWN_PAYMENT').val():0);
        let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val())?$('#BALANCE_PAYABLE').val():0);
        $('#MEMBERSHIP_PAYMENT_AMOUNT').val(parseFloat(total_bill).toFixed(2));
        $('#BALANCE_PAYABLE').val(parseFloat(total_bill-down_payment).toFixed(2));
        calculatePaymentPlans();
    }

    $(document).on('change', '.PAYMENT_METHOD', function () {
        $('.payment_method_div').slideUp();
        $('#down_payment_div').slideDown();
        $('#FIRST_DUE_DATE').prop('required', false);
        $('#IS_ONE_TIME_PAY').val(0);
        if ($(this).val() == 'One Time'){
            let total_bill = parseFloat(($('#total_bill').val())?$('#total_bill').val():0);
            $('#first_payment_date_div').slideUp();
            $('#DOWN_PAYMENT').val(0.00);
            $('#BALANCE_PAYABLE').val(total_bill.toFixed(2));
            $('#down_payment_div').slideUp();
            $('#AMOUNT_TO_PAY').val(total_bill.toFixed(2));
            $('#payment_confirmation_form_div').slideDown();
            $('#IS_ONE_TIME_PAY').val(1);
            $('#PAYMENT_BILLING_REF').val($('#BILLING_REF').val());
            $('#PAYMENT_BILLING_DATE').val($('#BILLING_DATE').val());
            openModel();
        }
        if ($(this).val() == 'Payment Plans'){
            $('#FIRST_DUE_DATE').prop('required', true);
            $('#payment_plans_div').slideDown();
        }
        if ($(this).val() == 'Flexible Payments'){
            $('#flexible_plans_div').slideDown();
            let total_bill = parseFloat(($('#total_bill').val())?$('#total_bill').val():0);
            $('#DOWN_PAYMENT').val(0.00);
            $('#BALANCE_PAYABLE').val(total_bill.toFixed(2));
            $('#down_payment_div').slideUp();
            $('#AMOUNT_TO_PAY').val(total_bill.toFixed(2));
            $('#payment_confirmation_form_div').slideDown();
            //openModel();
        }
    });

    function calculatePaymentPlans() {
        let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val())?$('#BALANCE_PAYABLE').val():0);
        let NUMBER_OF_PAYMENT = parseInt(($('#NUMBER_OF_PAYMENT').val())?$('#NUMBER_OF_PAYMENT').val():1);
        $('#INSTALLMENT_AMOUNT').val(parseFloat(balance_payable/NUMBER_OF_PAYMENT).toFixed(2));
    }

    function calculateNumberOfPayment(param) {
        let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val())?$('#BALANCE_PAYABLE').val():0);
        let entered_amount = $(param).val();
        let number_of_payment = balance_payable/entered_amount;
        $('#NUMBER_OF_PAYMENT').val(number_of_payment);
        if (Number.isInteger(number_of_payment)) {
            $('#number_of_payment_error').hide();
        }else {
            $('#number_of_payment_error').show();
        }
    }

    $(document).on('submit', '#billing_form', function (event) {
        event.preventDefault();
        let total_bill = parseFloat(($('#total_bill').val())?$('#total_bill').val():0);
        let down_payment = parseFloat(($('#DOWN_PAYMENT').val())?$('#DOWN_PAYMENT').val():0);
        let total_flexible_payment = 0;
        $('.FLEXIBLE_PAYMENT_AMOUNT').each(function () {
            total_flexible_payment += parseFloat($(this).val());
        });
        total_flexible_payment = isNaN(total_flexible_payment)?0:total_flexible_payment;
        if ((total_flexible_payment+down_payment) <= total_bill) {
            let number_of_payment = $('#NUMBER_OF_PAYMENT').val();
            if (Number.isInteger(Number(number_of_payment))) {
                let form_data = $('#billing_form').serialize();
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: form_data,
                    dataType: 'json',
                    success: function (data) {
                        $('.PK_ENROLLMENT_BILLING').val(data.PK_ENROLLMENT_BILLING);
                        $('.PK_ENROLLMENT_LEDGER').val(data.PK_ENROLLMENT_LEDGER);
                        let today = new Date();
                        let firstPaymentDate = new Date($('#FIRST_DUE_DATE').val());

                        if (PK_SERVICE_CLASS == 1) {
                            let paymentDate = new Date($('#MEMBERSHIP_PAYMENT_DATE').val());
                            if ((today.getDate() + '/' + today.getMonth() === paymentDate.getDate() + '/' + paymentDate.getMonth())) {
                                let balance_payable = parseFloat(($('#MEMBERSHIP_PAYMENT_AMOUNT').val()) ? $('#MEMBERSHIP_PAYMENT_AMOUNT').val() : 0);
                                $('#AMOUNT_TO_PAY').val(balance_payable.toFixed(2));
                                $('#payment_confirmation_form_div').slideDown();
                            } else {
                                window.location.href = 'customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER;
                            }
                        } else {
                            if (($('.PAYMENT_METHOD:checked').val() === 'One Time') || (parseFloat($('#DOWN_PAYMENT').val()) > 0) || ($('.PAYMENT_METHOD:checked').val() === 'Payment Plans' && (today.getDate() + '/' + today.getMonth() === firstPaymentDate.getDate() + '/' + firstPaymentDate.getMonth()))) {
                                if ($('.PAYMENT_METHOD:checked').val() === 'One Time') {
                                    let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);
                                    $('#AMOUNT_TO_PAY').val(balance_payable.toFixed(2));
                                } else {
                                    if (parseFloat($('#DOWN_PAYMENT').val()) > 0) {
                                        let down_payment = parseFloat(($('#DOWN_PAYMENT').val()) ? $('#DOWN_PAYMENT').val() : 0);
                                        $('#AMOUNT_TO_PAY').val(down_payment.toFixed(2));
                                    } else {
                                        if ($('.PAYMENT_METHOD:checked').val() === 'Payment Plans' && (today.getDate() + '/' + today.getMonth() === firstPaymentDate.getDate() + '/' + firstPaymentDate.getMonth())) {
                                            let installment_amount = parseFloat(($('#INSTALLMENT_AMOUNT').val()) ? $('#INSTALLMENT_AMOUNT').val() : 0);
                                            $('#AMOUNT_TO_PAY').val(installment_amount.toFixed(2));
                                        }
                                    }
                                }
                                $('#payment_confirmation_form_div').slideDown();
                                openModel();

                            } else {
                                window.location.href = 'customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER;
                            }
                        }
                    }
                });
            } else {
                $('#number_of_payment_error').slideUp();
                $('#number_of_payment_error').slideDown();
            }
        }else {
            alert('Total Bill Amount Exceed');
        }
    });

    /*$(document).on('submit', '#payment_confirmation_form', function (event) {
        event.preventDefault();
        let form_data = $('#payment_confirmation_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            success:function (data) {
                //window.location.href='customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER;
            }
        });
    });*/

    function payNow(PK_ENROLLMENT_LEDGER, BILLED_AMOUNT) {
        $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#AMOUNT_TO_PAY').val(BILLED_AMOUNT);
        $('#payment_confirmation_form_div').slideDown();
        openModel();
    }

    function getCreditCardList() {
        let PK_USER_MASTER = $('#PK_USER_MASTER').val();
        let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();
        $.ajax({
            url: "ajax/get_credit_card_list.php",
            type: 'POST',
            data: {PK_USER_MASTER: PK_USER_MASTER, PAYMENT_GATEWAY: PAYMENT_GATEWAY},
            success: function (data) {
                $('#card_list').html(data);
            }
        });
    }

    function selectRemainingPaymentType(param){
        let paymentType = $("#PK_PAYMENT_TYPE_REMAINING option:selected").text();
        let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();
        $('.remaining_payment_type_div').slideUp();
        $('#card-element').remove();
        switch (paymentType) {
            case 'Credit Card':
                if (PAYMENT_GATEWAY == 'Stripe') {
                    $('#card_div').html(`<div id="card-element"></div>`);
                    stripePaymentFunction();
                }
                $('#remaining_credit_card_payment').slideDown();
                break;

            case 'Check':
                $('#remaining_check_payment').slideDown();
                break;

            case 'Cash':
            default:
                $('.remaining_payment_type_div').slideUp();
                break;
        }
    }

    $(document).on('click', '.credit-card', function () {
        $('.credit-card').css("opacity", "1");
        $(this).css("opacity", "0.6");
    });
</script>
