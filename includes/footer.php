<footer class="footer">© <?= date('Y'); ?> Doable LLC</footer>

<!-- ============================= -->
<!-- jQuery (ONLY ONCE) -->
<!-- ============================= -->
<script src="../assets/node_modules/jquery/dist/jquery.min.js"></script>

<!-- ============================= -->
<!-- Bootstrap (ONLY bundle) -->
<!-- ============================= -->
<script src="../assets/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

<!-- ============================= -->
<!-- Core plugins -->
<!-- ============================= -->
<script src="../assets/dist/js/perfect-scrollbar.jquery.min.js"></script>
<script src="../assets/dist/js/waves.js"></script>
<script src="../assets/dist/js/sidebarmenu.js"></script>
<script src="../assets/node_modules/sticky-kit-master/dist/sticky-kit.min.js"></script>
<script src="../assets/node_modules/sparkline/jquery.sparkline.min.js"></script>
<script src="../assets/dist/js/custom.min.js"></script>

<!-- ============================= -->
<!-- Editors / Upload -->
<!-- ============================= -->
<script src="../assets/node_modules/html5-editor/wysihtml5-0.3.0.js"></script>
<script src="../assets/node_modules/html5-editor/bootstrap-wysihtml5.js"></script>
<script src="../assets/node_modules/dropzone-master/dist/dropzone.js"></script>

<!-- ============================= -->
<!-- jQuery UI (AFTER jQuery) -->
<!-- ============================= -->
<script src="../assets/dist/js/jquery-ui.js"></script>

<!-- ============================= -->
<!-- Validation / Tables -->
<!-- ============================= -->
<script src="../assets/dist/js/pages/validation.js"></script>
<script src="../assets/node_modules/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="../assets/node_modules/datatables.net-bs4/js/dataTables.responsive.min.js"></script>

<!-- ============================= -->
<!-- Timepicker / Select -->
<!-- ============================= -->
<script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>
<script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>

<!-- ============================= -->
<!-- Input mask -->
<!-- ============================= -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/3.3.4/jquery.inputmask.bundle.js"></script>

<!-- ============================= -->
<!-- SweetAlert2 -->
<!-- ============================= -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
    /*$(document)
        .ajaxStart(function () {
            //ajax request went so show the loading image
            $('.preloader').fadeIn();
        })
        .ajaxStop(function () {
            //got response so hide the loading image
            $('.preloader').fadeOut();
        });*/
</script>

<script>
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function selectDefaultLocation(param) {
        let DEFAULT_LOCATION_ID = $(param).val();
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

    function showHiddenPageNumber(param) {
        $(param).toggle("slide:left");
        $(param).closest('div .pagination').find('.hidden').toggle("slide:right");
    }
    $('.multi_select_location').SumoSelect({
        placeholder: 'Select Location',
        selectAll: true,
        okCancelInMulti: true,
        triggerChangeCombined: true
    });
    /*! function(window, document, $) {
        "use strict";
        $("input,select,textarea").not("[type=submit]").jqBootstrapValidation();
    }(window, document, jQuery);*/

    /*$(document).ready(function() {
        $('.textarea_editor').wysihtml5();
    });*/

    /*$.ajaxSetup({
        beforeSend: function() {
            $('.preloader').show();
        },
        complete: function() {
            $('.preloader').hide();
        },
    });*/
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