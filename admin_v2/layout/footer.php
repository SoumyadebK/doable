<footer class="footer">© <?= date('Y'); ?> Doable LLC</footer>

<script>
    // Initialize multi-select dropdown
    document.addEventListener('DOMContentLoaded', function() {
        // Set initial checked locations
        const initialLocations = ['AMTO', 'AMWH'];
        initialLocations.forEach(loc => {
            const checkbox = document.querySelector(`.location-checkbox[value="${loc}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });

        // Prevent dropdown from closing when clicking inside the multi-select dropdown
        const multiSelectDropdown = document.getElementById('locationMultiSelect');
        if (multiSelectDropdown) {
            multiSelectDropdown.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        }
    });

    function applyLocations() {
        const checkedCheckboxes = document.querySelectorAll('.location-checkbox:checked');
        const selectedValues = Array.from(checkedCheckboxes).map(cb => cb.value);

        // Update the display text
        const displayText = selectedValues.length > 0 ? selectedValues.join(', ') : 'Select locations';
        document.querySelector('.selected-locations').textContent = displayText;

        // You can add additional logic here to save the selection or filter data
        console.log('Selected locations:', selectedValues);

        // Close the dropdown
        const dropdownButton = document.querySelector('[data-bs-toggle="dropdown"][href="location"]');
        if (dropdownButton) {
            const bsDropdown = new bootstrap.Dropdown(dropdownButton);
            bsDropdown.hide();
        }
    }

    function clearLocations() {
        // Uncheck all checkboxes
        document.querySelectorAll('.location-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });

        // Update display text
        document.querySelector('.selected-locations').textContent = 'Select locations';
    }

    // Optional: Add keyboard support (Enter to apply, Escape to close)
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' && document.querySelector('#locationMultiSelect').offsetParent !== null) {
            applyLocations();
        }
    });


    function selectViewingLocation() {
        const checkedCheckboxes = document.querySelectorAll('.location-checkbox:checked');
        const DEFAULT_LOCATION_ID = Array.from(checkedCheckboxes).map(cb => cb.value);

        if (DEFAULT_LOCATION_ID.length === 0) {
            alert('Please select at least one location.');
            return;
        } else {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {
                    FUNCTION_NAME: 'selectDefaultLocation',
                    DEFAULT_LOCATION_ID: DEFAULT_LOCATION_ID
                },
                async: false,
                cache: false,
                success: function(result) {
                    //console.log(result);
                    window.location.reload();
                }
            });
        }
    }
</script>

<script>
    $(document).ready(function() {
        $('.minus').click(function() {
            let $input = $(this).parent().find('input');
            let count = parseInt($input.val()) - 1;
            count = count < 1 ? 1 : count;
            $input.val(count);
            $input.change();
            return false;
        });
        $('.plus').click(function() {
            let $input = $(this).parent().find('input');
            $input.val(parseInt($input.val()) + 1);
            $input.change();
            return false;
        });
    });

    function getCartItemList() {
        $.ajax({
            url: "../includes/get_cart_item_list.php",
            type: 'GET',
            data: {},
            success: function(data) {
                $('#cart_item_list').html(data);
            }
        });
    }

    function removeFromCart(PK_PRODUCT) {
        let conf = confirm("Are you sure you want to remove this item from cart?");
        if (conf) {
            $.ajax({
                url: "ajax/AjaxFunctionProductPurchase.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'removeFromCart',
                    PK_PRODUCT: PK_PRODUCT
                },
                success: function(data) {
                    $('#cart_count').text(data);
                }
            });
        }
    }
</script>