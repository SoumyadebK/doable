<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "ACTIVE CUSTOMERS ENROLLMENT REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

if (!empty($_GET['selected_range'])) {
    $selected_range = $_GET['selected_range'];
    $selected_date = date('Y-m-d', strtotime($_GET['selected_date']));
    $query = "SELECT DISTINCT DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER LEFT JOIN DOA_MASTER.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.ACTIVE =1 AND DOA_USERS.IS_DELETED = 0 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")  AND DOA_APPOINTMENT_MASTER.DATE >= DATE_SUB('" . $selected_date . "', INTERVAL " . $selected_range . " MONTH) AND DOA_APPOINTMENT_MASTER.DATE <= '" . $selected_date . "'";
} else {
    $selected_date = date('Y-m-d', strtotime($_GET['selected_date']));
    $query = "SELECT DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER FROM DOA_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_ENROLLMENT_MASTER.PK_USER_MASTER LEFT JOIN DOA_MASTER.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.ACTIVE =1 AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE = '" . date('Y-m-d', strtotime($selected_date)) . "'";
}

$today = date('Y-m-d');
$query = "SELECT DISTINCT DOA_ENROLLMENT_MASTER.* 
FROM `DOA_ENROLLMENT_MASTER` 
LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER 
LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE = DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE 
WHERE DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE IS NULL";

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
                                <li class="breadcrumb-item"><a href="active_account_balance_report.php">Select Date & Range</a></li>
                                <li class="breadcrumb-item active"><a href="active_account_balance_report_details.php"><?= $title ?></a></li>
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

                                    <?php
                                    $service_data = $db_account->Execute($query);

                                    while (!$service_data->EOF) {
                                        $customer = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CUSTOMER_NAME FROM DOA_USERS LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = " . $service_data->fields['PK_USER_MASTER']);
                                    ?>
                                        <div class="border" style="margin: 10px;">
                                            <div class="row enrollment_div" style="font-size: 15px; padding: 8px;">
                                                <div class="col-2" style="text-align: center; margin-top: 1.5%;">
                                                    <p><strong><?= $customer->fields['CUSTOMER_NAME'] ?></strong></p>
                                                    <!-- <a href="enrollment.php?id=<?= $PK_ENROLLMENT_MASTER ?>"><?= ($enrollment_name . $enrollment_data->fields['ENROLLMENT_ID'] == null) ? $enrollment_name . $enrollment_data->fields['MISC_ID'] : $enrollment_name . $enrollment_data->fields['ENROLLMENT_ID'] ?></a> -->
                                                </div>
                                                <div class="col-10">
                                                    <table id="myTable" class="table table-striped border" style="margin: auto; ">
                                                        <thead>
                                                            <tr>
                                                                <th style="text-align: center;">Service Code</th>
                                                                <th style="text-align: center;">Enroll</th>
                                                                <th style="text-align: center;">Used</th>
                                                                <th style="text-align: center;">Scheduled</th>
                                                                <th style="text-align: center;">Remain</th>
                                                                <th style="text-align: center;">Balance</th>
                                                                <th style="text-align: center;">Paid</th>
                                                            </tr>
                                                        </thead>

                                                        <tbody>
                                                            <?php
                                                            $pending_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_MASTER.CHARGE_TYPE, DOA_ENROLLMENT_MASTER.PK_USER_MASTER FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $service_data->fields['PK_USER_MASTER']);
                                                            $pending_service_code_array = [];
                                                            while (!$pending_service_data->EOF) {
                                                                if ($pending_service_data->fields['CHARGE_TYPE'] == 'Membership') {
                                                                    $NUMBER_OF_SESSION = getSessionCreatedCount($pending_service_data->fields['PK_ENROLLMENT_SERVICE']);
                                                                } else {
                                                                    $NUMBER_OF_SESSION = $pending_service_data->fields['NUMBER_OF_SESSION'];
                                                                }
                                                                $SESSION_SCHEDULED = getSessionScheduledCount($pending_service_data->fields['PK_ENROLLMENT_SERVICE']);
                                                                $SESSION_COMPLETED = getSessionCompletedCount($pending_service_data->fields['PK_ENROLLMENT_SERVICE']);
                                                                $PRICE_PER_SESSION = $pending_service_data->fields['PRICE_PER_SESSION'];
                                                                $paid_session = ($PRICE_PER_SESSION > 0) ? number_format($pending_service_data->fields['TOTAL_AMOUNT_PAID'] / $PRICE_PER_SESSION, 2) : $NUMBER_OF_SESSION;
                                                                $remain_session = $NUMBER_OF_SESSION - ($SESSION_COMPLETED + $SESSION_SCHEDULED);
                                                                $ps_balance = $paid_session - $SESSION_COMPLETED;

                                                                //if ($remain_session > 0) {
                                                                if (isset($pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']])) {
                                                                    $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['CODE'] = $pending_service_data->fields['SERVICE_CODE'];
                                                                    $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['ENROLL'] += $NUMBER_OF_SESSION;
                                                                    $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['REMAIN'] += $remain_session;
                                                                    $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['PAID'] += $pending_service_data->fields['TOTAL_AMOUNT_PAID'];
                                                                    $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['USED'] += $SESSION_COMPLETED;
                                                                    $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['SCHEDULED'] += $SESSION_SCHEDULED;
                                                                    $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['BALANCE'] += $ps_balance;
                                                                } else {
                                                                    $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['CODE'] = $pending_service_data->fields['SERVICE_CODE'];
                                                                    $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['ENROLL'] = $NUMBER_OF_SESSION;
                                                                    $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['REMAIN'] = $remain_session;
                                                                    $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['PAID'] = $pending_service_data->fields['TOTAL_AMOUNT_PAID'];
                                                                    $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['USED'] = $SESSION_COMPLETED;
                                                                    $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['SCHEDULED'] = $SESSION_SCHEDULED;
                                                                    $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['BALANCE'] = $ps_balance;
                                                                }
                                                                //}

                                                                $pending_service_data->MoveNext();
                                                            } ?>
                                                            <?php foreach ($pending_service_code_array as $service_code) { ?>
                                                                <tr>
                                                                    <td style="text-align: center;"><?= $service_code['CODE'] ?></td>
                                                                    <td style="text-align: center;"><?= $service_code['ENROLL'] ?></td>
                                                                    <td style="text-align: center;"><?= $service_code['USED'] ?></td>
                                                                    <td style="text-align: center;"><?= $service_code['SCHEDULED'] ?></td>
                                                                    <td style="text-align: center;"><?= $service_code['REMAIN'] ?></td>
                                                                    <td style="text-align: center; color:<?= ($service_code['BALANCE'] < 0) ? 'red' : 'black' ?>;"><?= $service_code['BALANCE'] ?></td>
                                                                    <td style="text-align: center;">$<?= number_format($service_code['PAID'], 2) ?></td>
                                                                </tr>
                                                            <?php } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    <?php
                                        $service_data->MoveNext();
                                    } ?>
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