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

$header = "payment_due.php?selected_date=" . $_GET['selected_date'] . "&type=view";

// Helper function to determine status based on due date
function getPaymentStatus($due_date)
{
    $today = date('Y-m-d');
    if ($due_date < $today) {
        return ['label' => 'Overdue', 'class' => 'status-overdue'];
    } elseif ($due_date == $today) {
        return ['label' => 'Due Today', 'class' => 'status-due-today'];
    } else {
        return ['label' => 'Upcoming', 'class' => 'status-upcoming'];
    }
}

// Fetch all payment due records
$payment_rows = [];
$row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.STATUS, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_LEDGER, DOA_ENROLLMENT_LEDGER.BILLED_AMOUNT, DOA_ENROLLMENT_LEDGER.AMOUNT_REMAIN, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_LEDGER.DUE_DATE, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT FROM DOA_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_LEDGER ON DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.STATUS NOT IN ('C', 'CA') AND DOA_ENROLLMENT_LEDGER.IS_PAID = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") " . $due_date . " ORDER BY DOA_ENROLLMENT_LEDGER.DUE_DATE DESC, DOA_ENROLLMENT_MASTER.PK_USER_MASTER ASC");
while (!$row->EOF) {
    $AMOUNT_TO_PAY = ($row->fields['AMOUNT_REMAIN'] > 0) ? $row->fields['AMOUNT_REMAIN'] : $row->fields['BILLED_AMOUNT'];
    $customer = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CUSTOMER_NAME, DOA_USERS.EMAIL_ID FROM DOA_USERS LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE PK_USER_MASTER = " . $row->fields['PK_USER_MASTER']);
    $selected_user_id = $customer->fields['PK_USER'];
    $selected_customer_id = $customer->fields['PK_USER_MASTER'];
    $customer_email = $customer->fields['EMAIL_ID'];
    $customer_name = $customer->fields['CUSTOMER_NAME'];

    $payment_rows[] = [
        'pk_enrollment_ledger' => $row->fields['PK_ENROLLMENT_LEDGER'],
        'pk_enrollment_master' => $row->fields['PK_ENROLLMENT_MASTER'],
        'customer_name' => $customer_name,
        'customer_email' => $customer_email,
        'selected_user_id' => $selected_user_id,
        'selected_customer_id' => $selected_customer_id,
        'enrollment_name' => $row->fields['ENROLLMENT_NAME'],
        'enrollment_id' => $row->fields['ENROLLMENT_ID'],
        'due_date' => $row->fields['DUE_DATE'],
        'due_date_formatted' => date('m-d-Y', strtotime($row->fields['DUE_DATE'])),
        'amount' => $AMOUNT_TO_PAY,
        'status' => getPaymentStatus($row->fields['DUE_DATE'])
    ];
    $row->MoveNext();
}
$total_payments = count($payment_rows);
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- jQuery UI Datepicker -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <style>
        body {
            background-color: #fcfcfc;
            color: #344054;
            font-size: 0.9rem;
        }

        .main-container {
            padding: 40px;
            max-width: 1400px;
            margin: auto;
        }

        /* Header & Filters */
        .icon-box {
            width: 48px;
            height: 48px;
            border: 1px solid #eaecf0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .filter-btn-group .btn {
            border: 1px solid #eaecf0;
            color: #667085;
            font-weight: 500;
            padding: 6px 16px;
            background: #fff;
        }

        .filter-btn-group .btn.active {
            color: #027a48;
            background-color: #f6fef9;
        }

        /* Table Customization */
        .table thead th {
            background-color: #f9fafb;
            color: #667085;
            font-weight: 500;
            border-bottom: 1px solid #eaecf0;
            padding: 12px 16px;
            vertical-align: middle;
        }

        .table tbody td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #eaecf0;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
            margin-right: 12px;
        }

        /* Status Badges */
        .badge-status {
            border-radius: 5px;
            padding: 4px 12px;
            font-weight: 500;
            border: 1px solid transparent;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-status::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-overdue {
            color: #b42318;
            border-color: #ddd;
        }

        .status-overdue::before {
            background: #d92d20;
        }

        .status-upcoming {
            color: #344054;
            border-color: #ddd;
        }

        .status-upcoming::before {
            background: #667085;
        }

        .status-due-today {
            color: #b54708;
            border-color: #ddd;
        }

        .status-due-today::before {
            background: #f79009;
        }

        .text-green {
            color: green;
        }

        .toolbar-btn {
            border: 1px solid #dee2e6;
            background: #fff;
            border-radius: 8px;
            font-size: 0.85rem;
            padding: 6px 12px;
            color: #444;
        }

        /* Ensure action buttons have visible borders */
        .btn-action.status-pill,
        .btn-action.payNowBtn,
        .editDueDateBtn,
        .saveDueDateBtn {
            border: 1px solid #d0d5dd !important;
            background-color: #ffffff !important;
            border-radius: 15px !important;
            padding: 4px 12px !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }

        .btn-action.status-pill:hover,
        .btn-action.payNowBtn:hover,
        .editDueDateBtn:hover {
            background-color: #f9fafb !important;
            border-color: #9aa4b2 !important;
        }

        .saveDueDateBtn {
            background-color: #f9fafb !important;
            border-color: #9aa4b2 !important;
            color: #ffffff !important;
        }

        .saveDueDateBtn:hover {
            background-color: #02633a !important;
            border-color: #02633a !important;
        }

        /* Pagination */
        .pagination-container {
            padding-top: 20px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .page-link-custom {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            text-decoration: none;
            color: var(--text-muted);
            border: 1px solid #ddd;
            border-radius: 20px;
        }

        .page-link-custom:first-child,
        .page-link-custom:last-child {
            border: 0;
        }

        .page-link-custom.active {
            background: #f2f4f7;
            color: #101828;
            font-weight: 600;
            border-radius: 20px;
        }

        .x-small {
            font-size: 0.75rem;
        }

        .btn-action {
            background: transparent;
            border: none;
        }

        .date-input-editing {
            width: 110px;
            padding: 4px 8px;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>

    <div class="container-fluid py-4 px-4 bg-white m-3 rounded border mx-auto">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box"><i class="bi bi-currency-dollar text-muted"></i></div>
                <div>
                    <h4 class="mb-0 fw-bold">Payment Due</h4>
                    <p class="text-muted mb-0"><?= htmlspecialchars($concatenatedResults) ?> | As of <?= date('m/d/Y', strtotime($selected_date)) ?></p>
                </div>
            </div>
            <div>
                <a href="payment_due_report.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="input-group w-25">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Search customer...">
            </div>
            <div class="d-flex gap-2">
                <div class="btn-group bg-white border rounded-pill p-1" id="statusFilterGroup">
                    <button class="btn btn-sm text-green rounded-pill px-3 filter-status active" data-status="all">All</button>
                    <button class="btn btn-sm text-muted px-3 filter-status" data-status="Overdue">Overdue</button>
                    <button class="btn btn-sm text-muted px-3 filter-status" data-status="Upcoming">Upcoming</button>
                    <button class="btn btn-sm text-muted px-3 filter-status" data-status="Due Today">Due Today</button>
                </div>
                <!-- <div class="input-group" style="width: auto;">
                    <span class="input-group-text bg-white" id="dateIconBtn" style="cursor: pointer; border-top-left-radius: 20px; border-bottom-left-radius: 20px;">
                        <i class="bi bi-calendar3"></i>
                    </span>
                    <input type="text" id="dateFilterInput" class="form-control" style="width: 130px; border-top-right-radius: 20px; border-bottom-right-radius: 20px; cursor: pointer;"
                        placeholder="Select Date" value="<?= date('m/d/Y', strtotime($selected_date)) ?>" readonly>
                </div>
                <button class="toolbar-btn" style="border-radius: 20px;"><i class="bi bi-filter"></i> Filter</button> -->
            </div>
        </div>

        <p class="text-muted small fw-medium mb-3"><span id="paymentCount"><?= $total_payments ?></span> payments due</p>

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table mb-0" id="paymentTable">
                    <thead>
                        <tr>

                            <th>Customer Name / Email</th>
                            <th>Enrollment</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach ($payment_rows as $row): ?>
                            <tr data-id="<?= $row['pk_enrollment_ledger'] ?>" data-status="<?= htmlspecialchars($row['status']['label']) ?>" data-customer="<?= strtolower(htmlspecialchars($row['customer_name'])) ?>">

                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-light text-dark">
                                            <?= strtoupper(substr($row['customer_name'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold">
                                                <a href="customer.php?id=<?= $row['selected_user_id'] ?>&master_id=<?= $row['selected_customer_id'] ?>&tab=enrollment" target="_blank" style="color: #344054; text-decoration: none;">
                                                    <?= htmlspecialchars($row['customer_name']) ?>
                                                </a>
                                            </div>
                                            <div class="text-muted x-small"><?= htmlspecialchars($row['customer_email'] ?: 'No email') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['enrollment_name']) ?>
                                    <span class="text-muted">(<?= htmlspecialchars($row['enrollment_id']) ?>)</span>
                                </td>
                                <td class="date-cell" data-date="<?= $row['due_date'] ?>"><?= $row['due_date_formatted'] ?></td>
                                <td class="fw-medium">$<?= number_format($row['amount'], 2) ?></td>
                                <td><span class="badge-status <?= $row['status']['class'] ?>"><?= $row['status']['label'] ?></span></td>
                                <td>
                                    <button class="btn btn-action me-2 status-pill payNowBtn"
                                        onclick="payNow(<?= $row['pk_enrollment_master'] ?>, <?= $row['pk_enrollment_ledger'] ?>, <?= $row['amount'] ?>, '<?= $row['enrollment_id'] ?>', <?= $row['selected_customer_id'] ?>);">
                                        Pay Now
                                    </button>
                                    <button class="btn btn-action me-2 status-pill editDueDateBtn">Edit Date</button>
                                    <button class="btn btn-action status-pill saveDueDateBtn" style="display: none;">Save</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payment_rows)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">No payment due records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center pagination-container px-3 py-2">
                <div>Showing <span id="showingStart">0</span> - <span id="showingEnd">0</span> of <span id="totalCount"><?= $total_payments ?></span></div>
                <div class="d-flex align-items-center gap-1" id="paginationControls"></div>
                <div>
                    <select class="form-select form-select-sm d-inline-block w-auto" id="rowsPerPage">
                        <option value="8">8 / page</option>
                        <option value="15">15 / page</option>
                        <option value="25">25 / page</option>
                        <option value="50">50 / page</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!--Payment Model-->
    <?php include('includes/enrollment_payment.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Client-side pagination, filtering, and search
        let allRows = [];
        let currentPage = 1;
        let rowsPerPage = 8;
        let currentStatusFilter = 'all';
        let currentSearchTerm = '';

        // Initialize data from table
        function initTableData() {
            allRows = [];
            document.querySelectorAll('#tableBody tr[data-id]').forEach(row => {
                allRows.push({
                    element: row,
                    id: row.getAttribute('data-id'),
                    status: row.getAttribute('data-status'),
                    customer: row.getAttribute('data-customer'),
                    dueDate: row.querySelector('.date-cell')?.getAttribute('data-date') || ''
                });
            });
            updateDisplay();
        }

        // Filter rows based on status and search
        function getFilteredRows() {
            return allRows.filter(row => {
                const matchesStatus = currentStatusFilter === 'all' || row.status === currentStatusFilter;
                const matchesSearch = currentSearchTerm === '' || row.customer.includes(currentSearchTerm);
                return matchesStatus && matchesSearch;
            });
        }

        // Update table display with pagination
        function updateDisplay() {
            const filtered = getFilteredRows();
            const totalFiltered = filtered.length;
            const totalPages = Math.ceil(totalFiltered / rowsPerPage);

            // Adjust current page if needed
            if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const rowsToShow = filtered.slice(start, end);

            // Hide all rows first
            allRows.forEach(row => row.element.style.display = 'none');

            // Show filtered and paginated rows
            rowsToShow.forEach(row => row.element.style.display = '');

            // Update UI counters
            document.getElementById('showingStart').textContent = totalFiltered > 0 ? start + 1 : 0;
            document.getElementById('showingEnd').textContent = Math.min(end, totalFiltered);
            document.getElementById('totalCount').textContent = totalFiltered;
            document.getElementById('paymentCount').textContent = totalFiltered;

            // Update pagination controls
            updatePaginationControls(totalPages);
        }

        function updatePaginationControls(totalPages) {
            const container = document.getElementById('paginationControls');
            if (!container) return;

            let html = '';
            html += `<a href="#" class="page-link-custom pagination-first" ${currentPage === 1 ? 'style="pointer-events:none; opacity:0.5"' : ''}><i class="bi bi-chevron-double-left"></i></a>`;
            html += `<a href="#" class="page-link-custom pagination-prev" ${currentPage === 1 ? 'style="pointer-events:none; opacity:0.5"' : ''}><i class="bi bi-chevron-left"></i></a>`;

            // Show page numbers
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);
            if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

            for (let i = startPage; i <= endPage; i++) {
                html += `<a href="#" class="page-link-custom pagination-page ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</a>`;
            }

            if (endPage < totalPages) {
                html += `<span>...</span>`;
                html += `<a href="#" class="page-link-custom pagination-page" data-page="${totalPages}">${totalPages}</a>`;
            }

            html += `<a href="#" class="page-link-custom pagination-next" ${currentPage === totalPages || totalPages === 0 ? 'style="pointer-events:none; opacity:0.5"' : ''}><i class="bi bi-chevron-right"></i></a>`;
            html += `<a href="#" class="page-link-custom pagination-last" ${currentPage === totalPages || totalPages === 0 ? 'style="pointer-events:none; opacity:0.5"' : ''}><i class="bi bi-chevron-double-right"></i></a>`;

            container.innerHTML = html;
        }

        // Event handlers for pagination
        function bindPaginationEvents() {
            document.querySelectorAll('.pagination-first').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (currentPage !== 1) {
                        currentPage = 1;
                        updateDisplay();
                    }
                });
            });
            document.querySelectorAll('.pagination-prev').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (currentPage > 1) {
                        currentPage--;
                        updateDisplay();
                    }
                });
            });
            document.querySelectorAll('.pagination-next').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const totalPages = Math.ceil(getFilteredRows().length / rowsPerPage);
                    if (currentPage < totalPages) {
                        currentPage++;
                        updateDisplay();
                    }
                });
            });
            document.querySelectorAll('.pagination-last').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const totalPages = Math.ceil(getFilteredRows().length / rowsPerPage);
                    currentPage = totalPages;
                    updateDisplay();
                });
            });
            document.querySelectorAll('.pagination-page').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const page = parseInt(btn.getAttribute('data-page'));
                    if (!isNaN(page)) {
                        currentPage = page;
                        updateDisplay();
                    }
                });
            });
        }

        // Search functionality
        document.getElementById('searchInput')?.addEventListener('input', function(e) {
            currentSearchTerm = e.target.value.toLowerCase();
            currentPage = 1;
            updateDisplay();
            bindPaginationEvents();
        });

        // Status filter
        document.querySelectorAll('.filter-status').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-status').forEach(b => b.classList.remove('active', 'text-green'));
                this.classList.add('active', 'text-green');
                currentStatusFilter = this.getAttribute('data-status');
                currentPage = 1;
                updateDisplay();
                bindPaginationEvents();
            });
        });


        // Date filter functionality - Fixed version
        $(function() {
            // Initialize datepicker on page load
            $('#dateFilterInput').datepicker({
                dateFormat: 'mm/dd/yy',
                defaultDate: new Date('<?= $selected_date ?>'),
                showOn: 'both', // This ensures the calendar shows on click
                buttonImageOnly: false,
                onSelect: function(dateText) {
                    // Convert from mm/dd/yy to yyyy-mm-dd for URL
                    const dateParts = dateText.split('/');
                    const formattedDate = `${dateParts[2]}-${dateParts[0].padStart(2, '0')}-${dateParts[1].padStart(2, '0')}`;

                    // Reload page with selected date
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('selected_date', formattedDate);
                    urlParams.set('type', 'view');
                    window.location.href = window.location.pathname + '?' + urlParams.toString();
                }
            });

            // Ensure calendar shows on both input click and icon click
            $('#dateFilterInput').on('click', function() {
                $(this).datepicker('show');
            });

            $('#dateIconBtn').on('click', function() {
                $('#dateFilterInput').datepicker('show');
            });
        });

        // Rows per page
        document.getElementById('rowsPerPage')?.addEventListener('change', function(e) {
            rowsPerPage = parseInt(e.target.value);
            currentPage = 1;
            updateDisplay();
            bindPaginationEvents();
        });

        // Select All checkbox
        document.getElementById('selectAll')?.addEventListener('change', function(e) {
            const isChecked = e.target.checked;
            document.querySelectorAll('#tableBody tr:visible .row-checkbox').forEach(cb => cb.checked = isChecked);
        });

        // Initialize
        initTableData();
        bindPaginationEvents();

        // Edit Due Date functionality
        document.querySelectorAll('.editDueDateBtn').forEach((btn, index) => {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                const dateCell = row.querySelector('.date-cell');
                const currentDate = dateCell.getAttribute('data-date');
                const editBtn = this;
                const saveBtn = row.querySelector('.saveDueDateBtn');

                // Create date input
                const input = document.createElement('input');
                input.type = 'text';
                input.value = currentDate;
                input.className = 'form-control date-input-editing';
                input.style.width = '120px';

                // Replace cell content
                dateCell.innerHTML = '';
                dateCell.appendChild(input);

                // Initialize datepicker
                $(input).datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0
                });

                editBtn.style.display = 'none';
                saveBtn.style.display = 'inline-block';

                // Handle save
                saveBtn.onclick = function() {
                    const updatedDate = input.value;
                    const ledgerId = row.getAttribute('data-id');

                    fetch('includes/save_due_date.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                id: ledgerId,
                                date: updatedDate
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                const formattedDate = new Date(updatedDate).toLocaleDateString('en-US');
                                dateCell.innerHTML = formattedDate;
                                dateCell.setAttribute('data-date', updatedDate);

                                // Update status based on new date
                                const today = new Date().toISOString().slice(0, 10);
                                let newStatus = '';
                                let statusClass = '';
                                if (updatedDate < today) {
                                    newStatus = 'Overdue';
                                    statusClass = 'status-overdue';
                                } else if (updatedDate === today) {
                                    newStatus = 'Due Today';
                                    statusClass = 'status-due-today';
                                } else {
                                    newStatus = 'Upcoming';
                                    statusClass = 'status-upcoming';
                                }
                                const statusSpan = row.querySelector('.badge-status');
                                statusSpan.textContent = newStatus;
                                statusSpan.className = `badge-status ${statusClass}`;
                                row.setAttribute('data-status', newStatus);

                                editBtn.style.display = 'inline-block';
                                saveBtn.style.display = 'none';

                                // Refresh filter display
                                updateDisplay();
                                bindPaginationEvents();
                            } else {
                                alert('Error saving date: ' + (data.message || 'Unknown error'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while saving');
                        });
                };
            });
        });

        // Pay Now functionality
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
</body>

</html>