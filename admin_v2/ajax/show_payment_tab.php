<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$PK_ENROLLMENT_MASTER = $_POST['PK_ENROLLMENT_MASTER'];
$enrollment_data = $db_account->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = '$PK_ENROLLMENT_MASTER'");
//$enrollment_number = $enrollment_data->fields['ENROLLMENT_ID'];
?>
<input type="hidden" name="PK_ENROLLMENT_SERVICE[]" value="<?= $enrollment_service_data->fields['PK_ENROLLMENT_SERVICE'] ?>">
<div class="form-check border rounded-2 p-2 mb-2">
    <div class="d-flex">
        <span class="checkicon d-inline-flex me-2 align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="12px" height="12px" viewBox="0 0 15 15" fill="#00922E">
                <path d="M7.5 6.75C8.49456 6.75 9.44839 7.14509 10.1517 7.84835C10.8549 8.55161 11.25 9.50544 11.25 10.5V15H3.75V10.5C3.75 9.50544 4.14509 8.55161 4.84835 7.84835C5.55161 7.14509 6.50544 6.75 7.5 6.75ZM2.466 9.0045C2.34664 9.40709 2.27614 9.82257 2.256 10.242L2.25 10.5V15H6.84877e-08V11.625C-0.000147605 10.9782 0.238521 10.3541 0.670226 9.87241C1.10193 9.39074 1.69627 9.08541 2.33925 9.015L2.46675 9.0045H2.466ZM12.534 9.0045C13.2014 9.04518 13.8282 9.33897 14.2864 9.82593C14.7447 10.3129 14.9999 10.9563 15 11.625V15H12.75V10.5C12.75 9.98025 12.675 9.4785 12.534 9.0045ZM2.625 4.5C3.12228 4.5 3.59919 4.69754 3.95083 5.04917C4.30246 5.40081 4.5 5.87772 4.5 6.375C4.5 6.87228 4.30246 7.34919 3.95083 7.70083C3.59919 8.05246 3.12228 8.25 2.625 8.25C2.12772 8.25 1.65081 8.05246 1.29917 7.70083C0.947544 7.34919 0.75 6.87228 0.75 6.375C0.75 5.87772 0.947544 5.40081 1.29917 5.04917C1.65081 4.69754 2.12772 4.5 2.625 4.5V4.5ZM12.375 4.5C12.8723 4.5 13.3492 4.69754 13.7008 5.04917C14.0525 5.40081 14.25 5.87772 14.25 6.375C14.25 6.87228 14.0525 7.34919 13.7008 7.70083C13.3492 8.05246 12.8723 8.25 12.375 8.25C11.8777 8.25 11.4008 8.05246 11.0492 7.70083C10.6975 7.34919 10.5 6.87228 10.5 6.375C10.5 5.87772 10.6975 5.40081 11.0492 5.04917C11.4008 4.69754 11.8777 4.5 12.375 4.5V4.5ZM7.5 0C8.29565 0 9.05871 0.316071 9.62132 0.87868C10.1839 1.44129 10.5 2.20435 10.5 3C10.5 3.79565 10.1839 4.55871 9.62132 5.12132C9.05871 5.68393 8.29565 6 7.5 6C6.70435 6 5.94129 5.68393 5.37868 5.12132C4.81607 4.55871 4.5 3.79565 4.5 3C4.5 2.20435 4.81607 1.44129 5.37868 0.87868C5.94129 0.316071 6.70435 0 7.5 0V0Z" />
            </svg>
        </span>
        <label class="form-check-label text-dark">
            Enrollment Number
            <span class="statusarea ms-2 fw-normal"><span><?= $enrollment_data->fields['ENROLLMENT_ID'] ?></span></span>
        </label>
        <button type="button" class="bg-white boxshadow-sm p-0 border-0 rounded-4 ms-auto avatar-sm">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="-14 0 511 512" width="14px" height="14px" fill="CurrentColor">
                <path d="m.5 481.992188h300.078125v30.007812h-300.078125zm0 0"></path>
                <path d="m330.585938 481.992188h120.03125v30.007812h-120.03125zm0 0"></path>
                <path d="m483.464844 84.882812-84.867188-84.8710932-.011718-.0117188c-5.875 5.882812-313.644532 314.078125-313.75 314.183594l-57.59375 142.460937 142.46875-57.597656s181.703124-181.636719 313.753906-314.164063zm-42.421875.011719-35.917969 35.964844-42.375-42.371094 35.875-36.011719zm-99.46875 14.851563 42.34375 42.347656-21.199219 21.226562-42.320312-42.320312zm-238.554688 249.523437 31.597657 31.597657-53.042969 21.441406zm58.226563 15.789063-42.429688-42.433594 180.265625-180.503906 42.433594 42.433594zm0 0"></path>
            </svg>
        </button>
    </div>
    <?php
    $total = 0;
    $enrollment_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_CODE.SERVICE_CODE FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
    while (!$enrollment_service_data->EOF) {
        if($enrollment_service_data->fields['DISCOUNT_TYPE'] > 0) {
            $discount = $enrollment_service_data->fields['DISCOUNT']."%";
        } else {
            $discount = "$".$enrollment_service_data->fields['DISCOUNT'];
        }
        $total += $enrollment_service_data->fields['FINAL_AMOUNT']; ?>
        <div class="border rounded-2 p-2 mt-2">
            <div class="d-flex mb-0">
                <label class="form-check-label text-dark">
                    <?= $enrollment_service_data->fields['SERVICE_DETAILS'] ?>
                    <span class="badge ms-auto rounded-1" style="background-color: #ebf2ff; color: #6b82e2;"><?= $enrollment_service_data->fields['SERVICE_CODE'] ?></span>
                </label>
                <span class="f12 text-dark ms-auto"><?= number_format((float)$enrollment_service_data->fields['FINAL_AMOUNT'], 2, '.', ''); ?></span>
            </div>
            <div class="statusarea m-0">
                <span>Session: <?= $enrollment_service_data->fields['NUMBER_OF_SESSION'] ?></span>
                <span>Price/Session: $<?= number_format((float)$enrollment_service_data->fields['PRICE_PER_SESSION'], 2, '.', ''); ?></span>
                <span name="DISCOUNT[]">Discount: <?= $discount ?></span>
            </div>
        </div>
    <?php $enrollment_service_data->MoveNext();
    } ?>
    <div class="totalamount p-2 border rounded-2 d-inline-flex align-items-center f12 justify-content-between w-100 mt-2">
        <span>Total Amount</span>
        <span class="fw-semibold text-dark"><?= number_format((float)$total, 2, '.', ''); ?></span>
    </div>
</div>