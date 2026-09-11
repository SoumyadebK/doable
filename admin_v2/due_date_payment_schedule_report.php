<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "DUE DATE PAYMENT SCHEDULE REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (empty($_GET['due_date'])) {
    header("location:due_date_payment_schedule_report.php");
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : 'view';

$selected_date = date('Y-m-d', strtotime($_GET['due_date']));
$display_date  = date('m/d/Y', strtotime($selected_date));

// Exact match: only payments due ON the selected date
$due_date = "AND DOA_ENROLLMENT_LEDGER.DUE_DATE = '" . $selected_date . "'";

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';

$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$resultsArray = [];
while (!$results->EOF) {
    $resultsArray[] = $results->fields['LOCATION_NAME'];
    $results->MoveNext();
}
$totalResults = count($resultsArray);
$concatenatedResults = "";
foreach ($resultsArray as $key => $result) {
    $concatenatedResults .= $result;
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}

$payment_gateway_data = getPaymentGatewayData();

$PAYMENT_GATEWAY = $payment_gateway_data->fields['PAYMENT_GATEWAY_TYPE'];
$GATEWAY_MODE  = $payment_gateway_data->fields['GATEWAY_MODE'];

$SECRET_KEY = $payment_gateway_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $payment_gateway_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $payment_gateway_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $payment_gateway_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $payment_gateway_data->fields['LOCATION_ID'];

$AUTHORIZE_LOGIN_ID         = $payment_gateway_data->fields['LOGIN_ID'];
$AUTHORIZE_TRANSACTION_KEY  = $payment_gateway_data->fields['TRANSACTION_KEY'];
$AUTHORIZE_CLIENT_KEY       = $payment_gateway_data->fields['AUTHORIZE_CLIENT_KEY'];

$MERCHANT_ID            = $payment_gateway_data->fields['MERCHANT_ID'];
$API_KEY                = $payment_gateway_data->fields['API_KEY'];
$PUBLIC_API_KEY         = $payment_gateway_data->fields['PUBLIC_API_KEY'];

$header = "due_date_payment_schedule_report_details.php?due_date=" . urlencode($_GET['due_date']) . "&type=view";
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<style>
    .auto-pay-on {
        color: #198754;
        font-weight: 700;
    }

    .auto-pay-off {
        color: #dc3545;
        font-weight: 700;
    }

    .auto-pay-on i,
    .auto-pay-off i {
        font-size: 1.05em;
    }

    tfoot th {
        background: #f5f7fa;
        font-weight: 700;
    }

    .date-filter-card {
        border: 1px solid #e3e6ea;
        border-radius: 8px;
        padding: 12px 18px;
        background: #fafbfc;
        margin-bottom: 15px;
    }

    .date-filter-card .field-label {
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 4px;
        color: #333;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">

        <div class="page-wrapper" style="padding-top: 0px !important;">

            <div class="container-fluid body_content" style="margin-top: 0px;">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="due_date_payment_schedule_report.php">Select Date</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <?php if ($type === 'export') { ?>
                    <h3>Data export to Arthur Murray API Successfully</h3>
                <?php } else { ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">

                                    <!-- ===== DATE SELECTOR AT TOP ===== -->
                                    <div class="date-filter-card">
                                        <form method="get" action="" id="dateFilterForm" class="row align-items-end">
                                            <div class="col-md-3 col-sm-6">
                                                <label class="field-label">Select Date <span style="color:red">*</span></label>
                                                <input type="text"
                                                    id="due_date_picker"
                                                    name="due_date"
                                                    class="form-control datepicker-normal"
                                                    placeholder="Select Date"
                                                    value="<?= htmlspecialchars($display_date) ?>"
                                                    required>
                                            </div>
                                            <div class="col-md-3 col-sm-6" style="padding-top: 22px;">
                                                <button type="submit" class="btn btn-info text-white rounded-pill" style="background-color:#39B54A !important;">
                                                    <i class="fa fa-search"></i> View Report
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <!-- ===== END DATE SELECTOR ===== -->

                                    <div>
                                        <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                        <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-bordered" data-page-length='50'>
                                            <thead>
                                                <tr>
                                                    <th style="width:50%; text-align:center; font-weight:bold" colspan="3"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= " (" . $concatenatedResults . ")" ?></th>
                                                    <th style="width:50%; text-align:center; font-weight:bold" colspan="2">Payments Due on <?= $display_date ?></th>
                                                </tr>
                                                <tr>
                                                    <th style="width:25%; text-align:center">Customer Name</th>
                                                    <th style="width:25%; text-align:center">Enrollment Name</th>
                                                    <th style="width:12%; text-align:center">Due Date</th>
                                                    <th style="width:12%; text-align:center">Amount Due</th>
                                                    <th style="width:10%; text-align:center">Auto-Pay</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                $grand_total = 0;
                                                $row = $db_account->Execute("
                                                    SELECT
                                                        DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                                                        DOA_ENROLLMENT_MASTER.STATUS,
                                                        DOA_ENROLLMENT_MASTER.PK_USER_MASTER,
                                                        DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                                                        DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                                                        DOA_ENROLLMENT_MASTER.ACTIVE_AUTO_PAY,
                                                        DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_LEDGER,
                                                        DOA_ENROLLMENT_LEDGER.BILLED_AMOUNT,
                                                        DOA_ENROLLMENT_LEDGER.AMOUNT_REMAIN,
                                                        DOA_ENROLLMENT_LEDGER.DUE_DATE,
                                                        CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT
                                                    FROM DOA_ENROLLMENT_MASTER
                                                    INNER JOIN DOA_ENROLLMENT_LEDGER
                                                        ON DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                                                    INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER
                                                        ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                                    INNER JOIN $master_database.DOA_USERS AS DOA_USERS
                                                        ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER
                                                    WHERE DOA_USERS.ACTIVE = 1
                                                      AND DOA_USERS.IS_DELETED = 0
                                                      AND DOA_ENROLLMENT_MASTER.STATUS NOT IN ('C', 'CA')
                                                      AND DOA_ENROLLMENT_LEDGER.IS_PAID = 0
                                                      AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                                      " . $due_date . "
                                                    ORDER BY DOA_ENROLLMENT_LEDGER.DUE_DATE ASC, DOA_ENROLLMENT_MASTER.PK_USER_MASTER ASC
                                                ");

                                                while (!$row->EOF) {
                                                    $AMOUNT_TO_PAY = ($row->fields['AMOUNT_REMAIN'] > 0) ? $row->fields['AMOUNT_REMAIN'] : $row->fields['BILLED_AMOUNT'];
                                                    $AUTO_PAY = $row->fields['ACTIVE_AUTO_PAY'];
                                                    $grand_total += (float)$AMOUNT_TO_PAY;

                                                    $customer = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CUSTOMER_NAME FROM DOA_USERS LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE PK_USER_MASTER = " . $row->fields['PK_USER_MASTER']);
                                                    $selected_user_id = $customer->fields['PK_USER'];
                                                    $selected_customer_id = $customer->fields['PK_USER_MASTER'];
                                                ?>
                                                    <tr data-id="<?= $row->fields['PK_ENROLLMENT_LEDGER'] ?>">
                                                        <td style="text-align:left">
                                                            <a href="customer.php?id=<?= $selected_user_id ?>&master_id=<?= $selected_customer_id ?>&tab=enrollment" target="_blank" style="color:blue; font-weight:bold">
                                                                <?= $customer->fields['CUSTOMER_NAME'] ?>
                                                            </a>
                                                        </td>
                                                        <td style="text-align:center"><?= $row->fields['ENROLLMENT_NAME'] . " || " . $row->fields['ENROLLMENT_ID'] ?></td>
                                                        <td class="date" style="text-align:center" data-raw="<?= $row->fields['DUE_DATE'] ?>"><?= date('m-d-Y', strtotime($row->fields['DUE_DATE'])) ?></td>
                                                        <td style="text-align:right" data-order="<?= $AMOUNT_TO_PAY ?>">$<?= number_format($AMOUNT_TO_PAY, 2) ?></td>
                                                        <td style="text-align:center" data-order="<?= $AUTO_PAY ?>">
                                                            <?php if ($AUTO_PAY == 1): ?>
                                                                <span class="auto-pay-on"><i class="fa fa-check-circle"></i> ON</span>
                                                            <?php else: ?>
                                                                <span class="auto-pay-off"><i class="fa fa-times-circle"></i> OFF</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php $row->MoveNext();
                                                    $i++;
                                                } ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="3" style="text-align:right">Total Due on <?= $display_date ?>:</th>
                                                    <th style="text-align:right">$<?= number_format($grand_total, 2) ?></th>
                                                    <th></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>

    <!--Payment Model-->
    <?php include('includes/enrollment_payment.php'); ?>

</body>

</html>

<script>
    function payNow(PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, BILLED_AMOUNT, ENROLLMENT_ID, PK_USER_MASTER) {
        $('.partial_payment').show();
        $('#PARTIAL_PAYMENT').prop('checked', false);
        $('.partial_payment_div').slideUp();

        $('.PAYMENT_TYPE').val('');
        $('#remaining_amount_div').slideUp();

        $('#enrollment_number').text(ENROLLMENT_ID);
        $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
        $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#ACTUAL_AMOUNT').val(BILLED_AMOUNT);
        $('#AMOUNT_TO_PAY').val(BILLED_AMOUNT);
        $('#PK_USER_MASTER').val(PK_USER_MASTER);
        $('#enrollment_payment_modal').modal('show');
    }

    $(document).ready(function() {

        // Init datepicker on the top filter
        $('#due_date_picker').datepicker({
            format: 'mm/dd/yyyy',
            autoclose: true,
            todayHighlight: true
        });

        if (!$.fn.DataTable.isDataTable('#myTable')) {
            $('#myTable').DataTable({
                "pageLength": 50,
                "order": [],
                "columnDefs": [{
                    "orderable": false,
                    "targets": [5]
                }]
            });
        }

        // Event delegation so it works after pagination/sorting
        $(document).on('click', '.editBtn', function() {
            var row = $(this).closest('tr');
            var dateCell = row.find('.date');
            var rawDate = dateCell.attr('data-raw'); // yyyy-mm-dd

            dateCell.html('<input type="text" value="' + rawDate + '" class="edit-date form-control">');
            dateCell.find('.edit-date').datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0
            });

            row.find('.editBtn').hide();
            row.find('.saveBtn').show();
        });

        $(document).on('click', '.saveBtn', function() {
            var row = $(this).closest('tr');
            var dateCell = row.find('.date');
            var updatedDate = row.find('.edit-date').val(); // yyyy-mm-dd
            var id = row.attr('data-id');

            fetch('includes/save_due_date.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        id: id,
                        date: updatedDate
                    })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        var p = updatedDate.split('-');
                        dateCell.attr('data-raw', updatedDate);
                        dateCell.text(p[1] + '-' + p[2] + '-' + p[0]);
                        row.find('.saveBtn').hide();
                        row.find('.editBtn').show();
                    } else {
                        alert('Error saving data: ' + res.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('An error occurred while saving.');
                });
        });
    });
</script>