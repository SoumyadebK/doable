<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "PAYMENT DUE REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$selected_date = date('Y-m-d', strtotime($_GET['selected_date']));
$due_date = "AND DOA_ENROLLMENT_LEDGER.DUE_DATE <= '" . date('Y-m-d', strtotime($selected_date)) . "'";

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';

$location_name = '';
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$resultsArray = [];
while (!$results->EOF) {
    $resultsArray[] = $results->fields['LOCATION_NAME'];
    $results->MoveNext();
}
$totalResults = count($resultsArray);
$concatenatedResults = "";
foreach ($resultsArray as $key => $result) {
    // Append the current result to the concatenated string
    $concatenatedResults .= $result;

    // If it's not the last result, append a comma
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

$AUTHORIZE_LOGIN_ID         = $payment_gateway_data->fields['LOGIN_ID']; //"4Y5pCy8Qr";
$AUTHORIZE_TRANSACTION_KEY     = $payment_gateway_data->fields['TRANSACTION_KEY']; //"4ke43FW8z3287HV5";
$AUTHORIZE_CLIENT_KEY         = $payment_gateway_data->fields['AUTHORIZE_CLIENT_KEY']; //"8ZkyJnT87uFztUz56B4PfgCe7yffEZA4TR5dv8ALjqk5u9mr6d8Nmt8KHyp8s9Ay";

$MERCHANT_ID            = $payment_gateway_data->fields['MERCHANT_ID'];
$API_KEY                = $payment_gateway_data->fields['API_KEY'];
$PUBLIC_API_KEY         = $payment_gateway_data->fields['PUBLIC_API_KEY'];

$header = "payment_due_report_details.php?selected_date=" . $_GET['selected_date'] . "&type=view";
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
                                <li class="breadcrumb-item"><a href="payment_due_report.php">Select Date</a></li>
                                <li class="breadcrumb-item active"><a href="customer_summary_report.php"><?= $title ?></a></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <?php
                if ($type === 'export') {
                    echo "<h3>Data export to Arthur Murray API Successfully</h3>";
                    /*$data = json_decode($post_data);
                if (isset($data->error)) {
                    echo '<div class="alert alert-danger alert-dismissible" role="alert">'.$data->error_description.'</div>';
                } elseif (isset($data->errors)) {
                    if (isset($data->errors->errors[0])) {
                        echo '<div class="alert alert-danger alert-dismissible" role="alert">' . $data->errors->errors[0] . '</div>';
                    } else {
                        echo '<div class="alert alert-danger alert-dismissible" role="alert">'.$data->message.'</div>';
                    }
                } else {
                    echo "<h3>Data export to Arthur Murray API Successfully</h3>";
                }*/
                } else { ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div>
                                        <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                        <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-bordered" data-page-length='50'>
                                            <thead>
                                                <tr>
                                                    <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="3"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= $business_name . " (" . $concatenatedResults . ")" ?></th>
                                                    <th style="width:50%; text-align: center; font-weight: bold" colspan="2">Previous Pending Payments on or before <?= date('m/d/Y', strtotime($selected_date)) ?></th>
                                                </tr>
                                                <tr>
                                                    <th style="width:10%; text-align: center">Customer Name</th>
                                                    <th style="width:10%; text-align: center">Enrollment Name</th>
                                                    <th style="width:10%; text-align: center">Due Date</th>
                                                    <th style="width:10%; text-align: center">Pending Payments</th>
                                                    <th style="width:10%; text-align: center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                $row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_LEDGER, DOA_ENROLLMENT_LEDGER.BILLED_AMOUNT, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_LEDGER.DUE_DATE, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT FROM DOA_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_LEDGER ON DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_LEDGER.IS_PAID = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") " . $due_date . " ORDER BY DOA_ENROLLMENT_LEDGER.DUE_DATE DESC, DOA_ENROLLMENT_MASTER.PK_USER_MASTER ASC");
                                                while (!$row->EOF) {
                                                    $customer = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CUSTOMER_NAME FROM DOA_USERS LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE PK_USER_MASTER = " . $row->fields['PK_USER_MASTER']);
                                                    $selected_user_id = $customer->fields['PK_USER'];
                                                    $selected_customer_id = $customer->fields['PK_USER_MASTER'];
                                                ?>
                                                    <tr data-id="<?= $row->fields['PK_ENROLLMENT_LEDGER'] ?>">
                                                        <td style="text-align: left"><a href="customer.php?id=<?= $selected_user_id ?>&master_id=<?= $selected_customer_id ?>&tab=enrollment" target="_blank" style="color: blue; font-weight: bold"><?= $customer->fields['CUSTOMER_NAME'] ?></a></td>
                                                        <td style="text-align: center"><?= $row->fields['ENROLLMENT_NAME'] . " || " . $row->fields['ENROLLMENT_ID'] ?></td>
                                                        <td class="date" style="text-align: center"><?= date('m-d-Y', strtotime($row->fields['DUE_DATE'])) ?></td>
                                                        <td style="text-align: right">$<?= $row->fields['BILLED_AMOUNT'] ?></td>
                                                        <td style="text-align: center">
                                                            <button id="payNow" class="pay_now_button btn btn-info waves-effect waves-light m-l-10 text-white" onclick="payNow(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>, <?= $row->fields['PK_ENROLLMENT_LEDGER'] ?>, <?= $row->fields['BILLED_AMOUNT'] ?>, '<?= $row->fields['ENROLLMENT_ID'] ?>', <?= $selected_customer_id ?>);">Pay Now</button>
                                                            <button class="editBtn btn btn-info waves-effect waves-light m-r-10 text-white myBtn">Edit Due Date</button>
                                                            <button class="saveBtn btn btn-info waves-effect waves-light m-r-10 text-white myBtn" style="display: none">Save Due Date</button>
                                                        </td>
                                                    </tr>
                                                <?php $row->MoveNext();
                                                    $i++;
                                                } ?>
                                            </tbody>
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
        //$('#payment_confirmation_form_div_customer').slideDown();
        //openPaymentModel();
        $('#enrollment_payment_modal').modal('show');
    }
</script>

<script>
    // Get all the edit buttons and save buttons in the table
    const editButtons = document.querySelectorAll('.editBtn');
    const saveButtons = document.querySelectorAll('.saveBtn');

    // Loop through each edit button and add an event listener
    editButtons.forEach((editButton, index) => {
        editButton.addEventListener('click', function() {
            // Find the row containing the clicked button
            const row = editButton.closest('tr');
            const dateCell = row.querySelector('.date');

             // Replace the cell content with input fields containing the current text
                dateCell.innerHTML = `<input type="text" value="${dateCell.textContent}" class="edit-date form-control">`;
 
                // Initialize datepicker on the new input
                $(dateCell.querySelector('.edit-date')).datepicker({
                    dateFormat: 'yy-mm-dd', // Set your preferred format
                    minDate: 0 // Optional: disable past dates
                });

            // Show the save button and hide the edit button
            editButton.style.display = 'none';
            saveButtons[index].style.display = 'inline-block';
        });
    });

    // Loop through each save button and add an event listener
    saveButtons.forEach((saveButton, index) => {
        saveButton.addEventListener('click', function() {
            // Find the row containing the clicked button
            const row = saveButton.closest('tr');

            const dateCell = row.querySelector('.date');

            const editDate = row.querySelector('.edit-date');

            // Get the updated values from the input fields
            const updatedDate = editDate.value;

            // Assuming you have an ID field (for example, as a data attribute)
            const id = row.getAttribute('data-id');

            // Prepare the data to be sent
            const data = {
                id: id,
                date: updatedDate,
            };

            // Send the updated data to the backend using Fetch API
            fetch('includes/save_due_date.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data) // Send data as form URL-encoded
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Replace the input fields with the updated text
                        dateCell.textContent = updatedDate;

                        // Show the edit button and hide the save button
                        saveButton.style.display = 'none';
                        editButtons[index].style.display = 'inline-block';
                    } else {
                        alert('Error saving data: ' + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    });
</script>