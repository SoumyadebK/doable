<?php
require_once('../global/config.php');

global $db;
global $db_account;
global $master_database;
global $results_per_page;

$title = "All Customers";

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$status_check = empty($_GET['status']) ? 'active' : $_GET['status'];

if ($status_check == 'active') {
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

if (isset($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = " AND (DOA_USERS.FIRST_NAME LIKE '%" . $search_text . "%' OR DOA_USERS.LAST_NAME LIKE '%" . $search_text . "%' OR DOA_USERS.USER_NAME LIKE '%" . $search_text . "%' OR DOA_USERS.PHONE LIKE '%" . $search_text . "%' OR DOA_CUSTOMER_DETAILS.PARTNER_FIRST_NAME LIKE '%" . $search_text . "%' OR DOA_CUSTOMER_DETAILS.PARTNER_LAST_NAME LIKE '%" . $search_text . "%')";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), count(DOA_USERS.PK_USER) AS TOTAL_RECORDS FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $account_database.DOA_CUSTOMER_DETAILS AS DOA_CUSTOMER_DETAILS ON DOA_USER_MASTER.PK_USER_MASTER = DOA_CUSTOMER_DETAILS.PK_USER_MASTER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE (DOA_USER_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") OR DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (" . $DEFAULT_LOCATION_ID . ")) AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.ACTIVE = '$status' AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . $search);
$number_of_result =  ($query->RecordCount() > 0) ? $query->fields['TOTAL_RECORDS'] : 1;
$number_of_page = ceil($number_of_result / $results_per_page);

if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = $_GET['page'];
}

$page_first_result = ($page - 1) * $results_per_page;

?>

<!DOCTYPE html>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
<style>
    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #444;
    }

    .status-badge {
        background: #e6f4ea;
        color: #1e7e34;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
    }

    .table td,
    .table th {
        vertical-align: middle;
    }

    .header-actions button {
        margin-left: 8px;
    }

    .pagination .page-item.active .page-link {
        background-color: #198754;
        border-color: #198754;
    }

    .search-box {
        max-width: 300px;
    }

    .btn-new {
        background: #39b54a;
        color: #fff;
        border-radius: 999px;
        padding: 8px 20px !important;
        font-size: 14px;
        border: none;
    }


    .view-toggle {
        display: flex;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        overflow: hidden;
    }

    .view-btn {
        padding: 6px 16px;
        border: none;
        background: #fff;
        font-size: 14px;
        color: #6b7280;
    }

    .view-btn.active {
        color: #39b54a;
        font-weight: 600;
    }



    .view-btn-icon {
        padding: 6px 16px;
        border: none;
        background: #fff;
        font-size: 14px;
        color: #6b7280;
    }

    .view-btn-icon.active {
        color: #39b54a;
        font-weight: 600;
    }
</style>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php include 'layout/header.php'; ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid mt-4">
                <div class="card-box" style="margin-top: 20px;">

                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <div class="d-flex align-items-center" style="gap: 12px;">
                            <span class="avatar-large">
                                <i class="bi bi-people" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h4 class="mb-0">Customers</h4>
                                <small class="text-muted">Optionally describe this</small>
                            </div>
                        </div>

                        <button class="btn-new">
                            + Create New Customer
                        </button>
                    </div>


                    <!-- Filters -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <input type="text" class="form-control search-box" placeholder="Search...">


                        <div class="view-toggle m-r-15" style="height: 37px; margin-left: auto; margin-right: 12px;">
                            <button class="view-btn-icon <?= ($status_check == 'active') ? 'active' : '' ?>" onclick="window.location.href='all_customers.php?status=active'">
                                Active
                            </button>
                            <button class="view-btn-icon <?= ($status_check == 'inactive') ? 'active' : '' ?>" onclick="window.location.href='all_customers.php?status=inactive'">
                                Archived
                            </button>
                        </div>

                        <div class="view-toggle m-r-10" style="height: 37px; margin-right: 12px;">
                            <button class="view-btn-icon">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                        </div>
                        <div class="view-toggle" style="height: 37px;">
                            <button class="view-btn-icon">
                                <i class="fa fa-sort-amount-desc"></i> Sort By
                            </button>
                        </div>
                    </div>



                    <p class="text-muted f12"><?= $number_of_result ?> <?= ($status_check == 'inactive') ? 'archived' : 'active' ?> customers</p>

                    <!-- Table -->
                    <div class="table-responsive schedule-wrapper">
                        <table class="table align-middle schedule-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><input type="checkbox"></th>
                                    <th>
                                        <button type="button" class="bg-transparent p-0 border-0 theme-text-light">
                                            <span class="fw-semibold">Customer Name / Email</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="bg-transparent p-0 border-0 theme-text-light">
                                            <span class="fw-semibold">Primary Location</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="bg-transparent p-0 border-0 theme-text-light">
                                            <span class="fw-semibold">Phone</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="bg-transparent p-0 border-0 theme-text-light">
                                            <span class="fw-semibold">Total Paid</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="bg-transparent p-0 border-0 theme-text-light">
                                            <span class="fw-semibold">Credit</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="bg-transparent p-0 border-0 theme-text-light">
                                            <span class="fw-semibold">Balance</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="bg-transparent p-0 border-0 theme-text-light">
                                            <span class="fw-semibold">Status</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $i = $page_first_result + 1;
                                $customer_data = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_LOCATION.LOCATION_NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_CUSTOMER_DETAILS.PARTNER_FIRST_NAME, ' ', DOA_CUSTOMER_DETAILS.PARTNER_LAST_NAME) AS PARTNER_NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $account_database.DOA_CUSTOMER_DETAILS AS DOA_CUSTOMER_DETAILS ON DOA_USER_MASTER.PK_USER_MASTER = DOA_CUSTOMER_DETAILS.PK_USER_MASTER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_USER_MASTER.PRIMARY_LOCATION_ID WHERE (DOA_USER_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") OR DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (" . $DEFAULT_LOCATION_ID . ")) AND DOA_USER_ROLES.PK_ROLES = 4 AND (DOA_USERS.IS_DELETED = 0 || DOA_USERS.IS_DELETED IS NULL) AND DOA_USERS.ACTIVE = '$status' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . $search . " ORDER BY DOA_USERS.CREATED_ON DESC LIMIT " . $page_first_result . ',' . $results_per_page);
                                while (!$customer_data->EOF) {
                                    $total_paid = 0;
                                    $total_used = 0;

                                    $total_paid_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') AND (DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' || DOA_ENROLLMENT_PAYMENT.TYPE = 'Adjustment') AND DOA_ENROLLMENT_PAYMENT.IS_REFUNDED = 0 AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $customer_data->fields['PK_USER_MASTER']);
                                    $total_paid = $total_paid_data->fields['TOTAL_PAID'];

                                    $enr_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $customer_data->fields['PK_USER_MASTER']);
                                    while (!$enr_service_data->EOF) {
                                        $SESSION_COMPLETED = getSessionCompletedCount($enr_service_data->fields['PK_ENROLLMENT_SERVICE']);
                                        $total_used += ($SESSION_COMPLETED * $enr_service_data->fields['PRICE_PER_SESSION']);
                                        $enr_service_data->MoveNext();
                                    }

                                    $balance = 0;
                                    $enr_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $customer_data->fields['PK_USER_MASTER']);
                                    while (!$enr_service_data->EOF) {
                                        $balance += ($enr_service_data->fields['FINAL_AMOUNT'] - $enr_service_data->fields['TOTAL_AMOUNT_PAID']);
                                        $enr_service_data->MoveNext();
                                    }

                                    $selected_preferred_location = $db->Execute("SELECT DOA_LOCATION.LOCATION_NAME FROM DOA_USERS LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_USER_LOCATION.PK_LOCATION WHERE DOA_USER_MASTER.PK_USER_MASTER = " . $customer_data->fields['PK_USER_MASTER']);
                                    $preferred_location = [];
                                    while (!$selected_preferred_location->EOF) {
                                        $preferred_location[] = $selected_preferred_location->fields['LOCATION_NAME'];
                                        $selected_preferred_location->MoveNext();
                                    }

                                    $CUSTOMER_NAME = $customer_data->fields['NAME'];
                                    $customer = getProfileBadge($CUSTOMER_NAME);
                                    $customer_initial = $customer['initials'];
                                    $customer_color = $customer['color'];
                                ?>
                                    <tr style="height: 60px;">
                                        <td><input type="checkbox"></td>
                                        <td class="d-flex align-items-center" style="height: 60px;">
                                            <span class="avatarname" style="color: #fff; background-color: <?= $customer_color ?>;"><?= $customer_initial; ?></span>
                                            <div>
                                                <div><a href="../admin/customer.php?id=<?= $customer_data->fields['PK_USER'] ?>&master_id=<?= $customer_data->fields['PK_USER_MASTER'] ?>"><?= $CUSTOMER_NAME ?></a></div>
                                                <small class="text-muted"><?= $customer_data->fields['EMAIL_ID'] ?></small>
                                            </div>
                                        </td>
                                        <td><?= $customer_data->fields['LOCATION_NAME'] ?></td>
                                        <td><?= $customer_data->fields['PHONE'] ?></td>
                                        <td>$<?= str_replace(",", "", number_format(($total_paid), 2)) ?></td>
                                        <td>$<?= str_replace(",", "", number_format(($total_paid) - $total_used, 2)) ?></td>
                                        <td>$<?= str_replace(",", "", number_format($balance, 2)) ?></td>
                                        <td>
                                            <?php if ($customer_data->fields['ACTIVE'] == 1) { ?>
                                                <span class="status not-started" style="border: 1px solid #e1e1e1; background-color: #fff;">
                                                    <i class="fa fa-check-circle" style="font-size:15px; color:#35e235;"></i> Active
                                                </span>
                                            <?php } else { ?>
                                                <span class="status not-started" style="border: 1px solid #e1e1e1; background-color: #fff;">
                                                    <i class="fa fa-ban" style="font-size:15px; color:#ff0000;"></i> Archived
                                                </span>
                                            <?php } ?>
                                        </td>

                                        <td class="text-center" style="vertical-align: middle;">
                                            <a href="../admin/customer.php?id=<?= $customer_data->fields['PK_USER'] ?>&master_id=<?= $customer_data->fields['PK_USER_MASTER'] ?>">
                                                <button type="button" class="bg-transparent p-0 border-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1rem" height="1rem" fill="CurrentColor">
                                                        <circle cx="256" cy="256" r="48" />
                                                        <circle cx="256" cy="416" r="48" />
                                                        <circle cx="256" cy="96" r="48" />
                                                    </svg>
                                                </button>
                                            </a>
                                        </td>
                                    </tr>
                                <?php $customer_data->MoveNext();
                                    $i++;
                                } ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer -->
                    <div class="d-flex justify-content-between align-items-center mt-3 f12">

                        <small class="text-muted">Page <?= $page ?> of <?= $number_of_page ?></small>

                        <div class="center">
                            <div class="pagination outer">
                                <ul>
                                    <?php if ($page > 1) { ?>
                                        <li><a href="all_customers.php?status=<?= $status_check ?>&page=1">&laquo;</a></li>
                                        <li><a href="all_customers.php?status=<?= $status_check ?>&page=<?= ($page - 1) ?>">&lsaquo;</a></li>
                                    <?php }
                                    for ($page_count = 1; $page_count <= $number_of_page; $page_count++) {
                                        if ($page_count == $page || $page_count == ($page + 1) || $page_count == ($page - 1) || $page_count == $number_of_page) {
                                            echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="all_customers.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                        } elseif ($page_count == ($number_of_page - 1)) {
                                            echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                        } else {
                                            echo '<li><a class="hidden" href="all_customers.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                        }
                                    }
                                    if ($page < $number_of_page) { ?>
                                        <li><a href="all_customers.php?status=<?= $status_check ?>&page=<?= ($page + 1) ?>">&rsaquo;</a></li>
                                        <li><a href="all_customers.php?status=<?= $status_check ?>&page=<?= $number_of_page ?>">&raquo;</a></li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>

                        <select class="form-select form-select-sm" style="width: auto;">
                            <option>50 / page</option>
                            <option>100 / page</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>


    </div>

    <?php require_once('../includes/footer.php'); ?>

    <script>
        $(document).ready(function() {
            $("#FROM_DATE").datepicker({
                numberOfMonths: 1,
                onSelect: function(selected) {
                    $("#END_DATE").datepicker("option", "minDate", selected);
                    $("#FROM_DATE, #END_DATE").trigger("change");
                }
            });
            $("#END_DATE").datepicker({
                numberOfMonths: 1,
                onSelect: function(selected) {
                    $("#FROM_DATE").datepicker("option", "maxDate", selected)
                }
            });
        });

        $(document).ready(function() {
            $(".sortable").on("click", function() {
                var table = $(this).closest("table");
                var tbody = table.find("tbody");
                var rows = tbody.find("tr").toArray();
                var index = $(this).index();
                var asc = !$(this).hasClass("asc");
                var isDate = $(this).is("[data-date]");
                var type = $(this).data("type");

                // Remove old sorting indicators
                table.find(".sortable").removeClass("asc desc");
                $(this).addClass(asc ? "asc" : "desc");

                rows.sort(function(a, b) {
                    var A = $(a).children("td").eq(index).text().trim();
                    var B = $(b).children("td").eq(index).text().trim();

                    // Handle data type
                    if (isDate) {
                        A = new Date(A);
                        B = new Date(B);
                    } else if (type === "number") {
                        A = parseFloat(A.replace(/[^0-9.\-]/g, "")) || 0;
                        B = parseFloat(B.replace(/[^0-9.\-]/g, "")) || 0;
                    } else {
                        A = A.toLowerCase();
                        B = B.toLowerCase();
                    }

                    if (A < B) return asc ? -1 : 1;
                    if (A > B) return asc ? 1 : -1;
                    return 0;
                });

                // Append sorted rows
                $.each(rows, function(i, row) {
                    tbody.append(row);
                });
            });
        });

        function editpage(id) {
            //alert(i);
            window.location.href = "enrollment.php?id=" + id;
        }
    </script>
</body>

</html>