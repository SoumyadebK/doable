<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

?>
<?php
$service_counter = 1;
$package_service_data = $db_account->Execute("SELECT * FROM DOA_PACKAGE_SERVICE WHERE PK_PACKAGE = " . $_POST['PK_PACKAGE']);
while (!$package_service_data->EOF) {
    $unique_id = "package" . $service_counter; // Changed to match existing pattern but with counter
?>

    <!-- Added wrapper div to separate each service -->
    <div class="datetime-area f12 bg-light p-2 border rounded-2 mb-2" id="package_wrapper_<?= $service_counter ?>">
        <div class="datetime-item d-flex ">
            <div class="align-self-center">
                <?php
                $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE FROM `DOA_SERVICE_MASTER` WHERE DOA_SERVICE_MASTER.PK_SERVICE_MASTER = " . $package_service_data->fields['PK_SERVICE_MASTER'] . " AND DOA_SERVICE_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND ACTIVE = 1 AND IS_DELETED = 0");
                ?>
                <p class="text-dark fw-semibold mb-0"><?= $row->fields['SERVICE_NAME'] ?> <span class="badge border ms-auto" style="background-color: #ebf2ff; color: #6b82e2;">PRI</span></p>
                <span class="f10">Total: $<?= $package_service_data->fields['TOTAL'] ?></span>
            </div>
            <div class="d-flex gap-2 ms-auto align-items-start">
                <button type="button" class="bg-white theme-text-light border-0 rounded-circle avatar-sm delete-package-service" data-service-id="<?= $service_counter ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="-85 -19 617 617.33331" width="14px" height="14px" fill="#212529">
                        <path d="m219.121094 319.375c-6.894532.019531-12.480469 5.605469-12.5 12.5v152.5c0 6.90625 5.601562 12.5 12.5 12.5 6.902344 0 12.5-5.59375 12.5-12.5v-152.5c-.019532-6.894531-5.601563-12.480469-12.5-12.5zm0 0"></path>
                        <path d="m299.121094 319.375c-6.894532.019531-12.480469 5.605469-12.5 12.5v152.5c0 6.90625 5.601562 12.5 12.5 12.5 6.902344 0 12.5-5.59375 12.5-12.5v-152.5c-.019532-6.894531-5.601563-12.480469-12.5-12.5zm0 0"></path>
                        <path d="m139.121094 319.375c-6.894532.019531-12.480469 5.605469-12.5 12.5v152.5c0 6.90625 5.601562 12.5 12.5 12.5 6.902344 0 12.5-5.59375 12.5-12.5v-152.5c-.019532-6.894531-5.601563-12.480469-12.5-12.5zm0 0"></path>
                        <path d="m386.121094 64h-71.496094v-36.375c-.007812-15.257812-12.375-27.62109375-27.628906-27.625h-135.746094c-15.257812.00390625-27.621094 12.367188-27.628906 27.625v36.5h-71.496094c-27.515625.007812-51.003906 19.863281-55.582031 46.992188-4.582031 27.128906 11.09375 53.601562 37.078125 62.632812-.246094.894531-.371094 1.820312-.375 2.75v339.75c.015625 34.511719 27.988281 62.484375 62.5 62.5h246.875c34.511718-.015625 62.492187-27.988281 62.5-62.5v-339.75c.011718-.929688-.117188-1.855469-.375-2.75 26.019531-9.0625 41.6875-35.585938 37.078125-62.75s-28.152344-47.023438-55.703125-47zm-237.371094-36.375c.003906-1.449219 1.175781-2.617188 2.621094-2.625h135.753906c1.445312.007812 2.617188 1.175781 2.621094 2.625v36.5h-140.996094zm193.75 526.125h-246.753906c-20.683594-.058594-37.4375-16.816406-37.5-37.5v-339.375h321.875v339.375c-.117188 20.707031-16.914063 37.453125-37.621094 37.5zm43.621094-401.875h-333.996094c-17.332031 0-31.378906-14.046875-31.378906-31.375s14.046875-31.375 31.378906-31.375h333.996094c17.332031 0 31.378906 14.046875 31.378906 31.375s-14.046875 31.375-31.378906 31.375zm0 0"></path>
                    </svg>
                </button>
                <button type="button" class="bg-white theme-text-light border-0 rounded-circle avatar-sm btncollapse" data-bs-toggle="collapse" href="#<?= $unique_id ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28444 28444" width="14px" height="14px" fill="#212529">
                        <path d="m26891 9213-12669 12669-12669-12669 1768-1767 10901 10901 10902-10901z" fill-rule="nonzero"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div class="collapse" id="<?= $unique_id ?>">
            <!-- Sessions -->
            <div class="d-inline-flex gap-1">
                <div class="session-item">
                    <label class="small text-muted">No. of sessions</label>
                    <input type="number" class="form-control form-control-sm text-center NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" value="<?= $package_service_data->fields['NUMBER_OF_SESSION'] ?>">
                </div>
                <div class="session-item">
                    <label class="small text-muted">Price / session</label>
                    <div class="session-item position-relative">
                        <input type="text" class="form-control form-control-sm PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" value="<?= $package_service_data->fields['PRICE_PER_SESSION'] ?>" style="padding-left: 20px;">
                        <span class="position-absolute" style="top: 7px; left: 10px;">$</span>
                    </div>
                </div>

                <div class="session-item" style="min-width: 45px;">
                    <label class="small text-muted">Total</label>
                    <div class="f10 pt-2"><span class="TOTAL" name="TOTAL">$ <?= $package_service_data->fields['TOTAL'] ?></span></div>
                </div>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="f12 text-muted">Discount</label>
                <div class="form-check form-switch p-0 mb-0" style="min-height: auto;">
                    <input class="form-check-input" type="checkbox" <?= ($package_service_data->fields['DISCOUNT'] > 0) ? 'checked' : '' ?> name="HAS_DISCOUNT[]" onchange="toggleDiscount(this)">
                </div>
            </div>
            <div class="d-inline-flex gap-1">
                <div class="session-item">
                    <label class="small text-muted">Type</label>
                    <select class="form-select form-select-sm" style="min-width: 90px;" name="DISCOUNT_TYPE[]">
                        <option value="1" <?= ($package_service_data->fields['DISCOUNT_TYPE'] == 1) ? 'selected' : '' ?>>Fixed</option>
                        <option value="2" <?= ($package_service_data->fields['DISCOUNT_TYPE'] == 2) ? 'selected' : '' ?>>Percent</option>
                    </select>
                </div>
                <div class="session-item">
                    <label class="small text-muted">Value</label>
                    <div class="session-item position-relative">
                        <input type="text" class="form-control form-control-sm DISCOUNT" name="DISCOUNT[]" value="<?= $package_service_data->fields['DISCOUNT'] ?>" style="padding-left: 20px;">
                        <span class="position-absolute" style="top: 7px; left: 10px;">$</span>
                    </div>
                </div>
                <div class="session-item" style="min-width: 45px;">
                    <label class="small text-muted">Total</label>
                    <div class="f10 pt-2">$ <?= $package_service_data->fields['FINAL_AMOUNT'] ?></div>
                    <input type="hidden" class="FINAL_AMOUNT" name="FINAL_AMOUNT[]" value="<?= $package_service_data->fields['FINAL_AMOUNT'] ?>">
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- End of wrapper div -->

<?php
    $service_counter++;
    $package_service_data->MoveNext();
} ?>

<script>
    function toggleDiscount(checkbox) {
        const discountTypeSelect = checkbox.closest('.d-flex').nextElementSibling.querySelector('select');
        const discountValueInput = checkbox.closest('.d-flex').nextElementSibling.querySelector('input');
        if (checkbox.checked) {
            discountTypeSelect.removeAttribute('disabled');
            discountValueInput.removeAttribute('disabled');
        } else {
            discountTypeSelect.setAttribute('disabled', 'disabled');
            discountValueInput.setAttribute('disabled', 'disabled');
            discountTypeSelect.value = '';
            discountValueInput.value = '';
            calculateServiceTotal(discountValueInput);
        }
    }

    // Add delete functionality
    $(document).ready(function() {
        $('.delete-package-service').click(function() {
            $(this).closest('.individual_service_div').remove();

            // Recalculate total
            let total = 0;
            $('.FINAL_AMOUNT').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            $('.TOTAL_AMOUNT').text('$' + total.toFixed(2));
            $('.TOTAL_AMOUNT').val(total.toFixed(2));
        });
    });
</script>