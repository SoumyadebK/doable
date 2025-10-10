<?php
require_once('../global/config.php');
$title = "All Customers";
global $db;
global $db_account;
global $master_database;
global $results_per_page;

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
    table th {
        font-weight: bold;
    }

    .sortable.asc::after {
        content: " ▲";
    }

    .sortable.desc::after {
        content: " ▼";
    }
</style>
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
                    <div class="col-md-2 align-self-center">
                        <?php if ($status_check == 'inactive') { ?>
                            <h4 class="text-themecolor">Not Active Customers</h4>
                        <?php } elseif ($status_check == 'active') { ?>
                            <h4 class="text-themecolor">Active Customers</h4>
                        <?php } ?>
                    </div>

                    <?php if ($status_check == 'inactive') { ?>
                        <div class="col-md-3 align-self-center">
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_customers.php?status=active'"><i class="fa fa-user"></i> Show Active</button>
                        </div>
                    <?php } elseif ($status_check == 'active') { ?>
                        <div class="col-md-3 align-self-center">
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_customers.php?status=inactive'"><i class="fa fa-user-times"></i> Show Not Active</button>
                        </div>
                    <?php } ?>

                    <div class="col-md-4 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center" style="margin-right: 60%">
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='customer.php'"><i class="fa fa-plus-circle"></i> Create New</button>
                        </div>
                    </div>
                    <div class="col-md-3 align-self-center text-end">
                        <form class="form-material form-horizontal" action="" method="get">
                            <input type="hidden" name="status" value="<?= $status_check ?>">
                            <div class="input-group">
                                <input class="form-control" type="text" name="search_text" placeholder="Search.." value="<?= $search_text ?>">
                                <button class="btn btn-info waves-effect waves-light m-r-10 text-white input-group-btn m-b-1" type="submit"><i class="fa fa-search"></i></button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped border">
                                        <thead>
                                            <tr>
                                                <th data-type="number" class="sortable" style="cursor: pointer">No</th>
                                                <th data-type="string" class="sortable" style="width:10%; cursor: pointer;">Name</th>
                                                <th data-type="string" class="sortable" style="width:10%; cursor: pointer;">Primary Location</th>
                                                <th data-type="string" class="sortable" style="width:15%; cursor: pointer;">Preferred Locations</th>
                                                <th data-type="string" class="sortable" style="width:10%; cursor: pointer;">Partner</th>
                                                <th data-type="string" class="sortable" style="width:10%; cursor: pointer;">Customer ID</th>
                                                <th data-type="string" class="sortable" style="width:10%; cursor: pointer;">Email Id</th>
                                                <th data-type="string" class="sortable" style="width:10%; cursor: pointer;">Phone</th>
                                                <th data-type="number" class="sortable" style="width:10%; cursor: pointer;">Total Paid</th>
                                                <th data-type="number" class="sortable" style="width:10%; cursor: pointer;">Credit</th>
                                                <th data-type="number" class="sortable" style="width:10%; cursor: pointer;">Balance</th>
                                                <th style="width:10%;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = $page_first_result + 1;
                                            $row = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_LOCATION.LOCATION_NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_CUSTOMER_DETAILS.PARTNER_FIRST_NAME, ' ', DOA_CUSTOMER_DETAILS.PARTNER_LAST_NAME) AS PARTNER_NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $account_database.DOA_CUSTOMER_DETAILS AS DOA_CUSTOMER_DETAILS ON DOA_USER_MASTER.PK_USER_MASTER = DOA_CUSTOMER_DETAILS.PK_USER_MASTER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_USER_MASTER.PRIMARY_LOCATION_ID WHERE (DOA_USER_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") OR DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (" . $DEFAULT_LOCATION_ID . ")) AND DOA_USER_ROLES.PK_ROLES = 4 AND (DOA_USERS.IS_DELETED = 0 || DOA_USERS.IS_DELETED IS NULL) AND DOA_USERS.ACTIVE = '$status' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . $search . " ORDER BY DOA_USERS.CREATED_ON DESC LIMIT " . $page_first_result . ',' . $results_per_page);
                                            while (!$row->EOF) {
                                                makeExpiryEnrollmentComplete($row->fields['PK_USER_MASTER']);
                                                makeMiscComplete($row->fields['PK_USER_MASTER']);
                                                makeDroppedCancelled($row->fields['PK_USER_MASTER']);
                                                checkAllEnrollmentStatus($row->fields['PK_USER_MASTER']);


                                                $total_paid = 0;
                                                $total_used = 0;

                                                $total_paid_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') AND (DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' || DOA_ENROLLMENT_PAYMENT.TYPE = 'Adjustment') AND DOA_ENROLLMENT_PAYMENT.IS_REFUNDED = 0 AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $row->fields['PK_USER_MASTER']);
                                                $total_paid = $total_paid_data->fields['TOTAL_PAID'];

                                                $enr_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $row->fields['PK_USER_MASTER']);
                                                while (!$enr_service_data->EOF) {
                                                    $SESSION_COMPLETED = getSessionCompletedCount($enr_service_data->fields['PK_ENROLLMENT_SERVICE']);
                                                    $total_used += ($SESSION_COMPLETED * $enr_service_data->fields['PRICE_PER_SESSION']);
                                                    $enr_service_data->MoveNext();
                                                }

                                                $balance = 0;
                                                $enr_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $row->fields['PK_USER_MASTER']);
                                                while (!$enr_service_data->EOF) {
                                                    $balance += ($enr_service_data->fields['FINAL_AMOUNT'] - $enr_service_data->fields['TOTAL_AMOUNT_PAID']);
                                                    $enr_service_data->MoveNext();
                                                }

                                                $selected_preferred_location = $db->Execute("SELECT DOA_LOCATION.LOCATION_NAME FROM DOA_USERS LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_USER_LOCATION.PK_LOCATION WHERE DOA_USER_MASTER.PK_USER_MASTER = " . $row->fields['PK_USER_MASTER']);
                                                $preferred_location = [];
                                                while (!$selected_preferred_location->EOF) {
                                                    $preferred_location[] = $selected_preferred_location->fields['LOCATION_NAME'];
                                                    $selected_preferred_location->MoveNext();
                                                }
                                            ?>
                                                <tr>
                                                    <td onclick="editpage(<?= $row->fields['PK_USER'] ?>, <?= $row->fields['PK_USER_MASTER'] ?>);"><?= $i; ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_USER'] ?>, <?= $row->fields['PK_USER_MASTER'] ?>);"><?= $row->fields['NAME'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_USER'] ?>, <?= $row->fields['PK_USER_MASTER'] ?>);"><?= $row->fields['LOCATION_NAME'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_USER'] ?>, <?= $row->fields['PK_USER_MASTER'] ?>);"><?= implode(', ', $preferred_location) ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_USER'] ?>, <?= $row->fields['PK_USER_MASTER'] ?>);"><?= $row->fields['PARTNER_NAME'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_USER'] ?>, <?= $row->fields['PK_USER_MASTER'] ?>);"><?= $row->fields['USER_NAME'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_USER'] ?>, <?= $row->fields['PK_USER_MASTER'] ?>);"><?= $row->fields['EMAIL_ID'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_USER'] ?>, <?= $row->fields['PK_USER_MASTER'] ?>);"><?= $row->fields['PHONE'] ?></td>
                                                    <td style="text-align: right" onclick="editpage(<?= $row->fields['PK_USER'] ?>, <?= $row->fields['PK_USER_MASTER'] ?>);"><?= str_replace(",", "", number_format(($total_paid), 2)) ?></td>
                                                    <td style="text-align: right" onclick="editpage(<?= $row->fields['PK_USER'] ?>, <?= $row->fields['PK_USER_MASTER'] ?>);"><?= str_replace(",", "", number_format(($total_paid) - $total_used, 2)) ?></td>
                                                    <td style="text-align: right" onclick="editpage(<?= $row->fields['PK_USER'] ?>, <?= $row->fields['PK_USER_MASTER'] ?>);"><?= str_replace(",", "", number_format($balance, 2)) ?></td>
                                                    <td style="margin-top: auto; margin-bottom: auto">
                                                        <?php if ($row->fields['EMAIL_ID']): ?>
                                                            <a class="waves-dark" href="compose.php?sel_uid=<?= $row->fields['PK_USER'] ?>" aria-haspopup="true" aria-expanded="false" title="Email" style="font-size:18px"><i class="fa fa-envelope"></i></a>&nbsp;&nbsp;
                                                        <?php else: ?>
                                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <?php endif; ?>

                                                        <?php if (in_array('Customers Profile Edit', $PERMISSION_ARRAY)) { ?>
                                                            <a href="customer.php?id=<?= $row->fields['PK_USER'] ?>&master_id=<?= $row->fields['PK_USER_MASTER'] ?>" title="Edit" style="font-size:18px"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;
                                                        <?php } ?>
                                                        <?php if ($row->fields['ACTIVE'] == 1) { ?>
                                                            <span class="active-box-green"></span>
                                                        <?php } else { ?>
                                                            <span class="active-box-red"></span>
                                                        <?php } ?>
                                                        <!--<a href="all_customers.php?type=del&id=<?php /*=$row->fields['PK_SERVICE_MASTER']*/ ?>" onclick='ConfirmDelete(<?php /*=$row->fields['PK_USER']*/ ?>);' title="Delete"><i class="ti-trash" style="font-size: 20px;"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-->
                                                    </td>
                                                </tr>
                                            <?php $row->MoveNext();
                                                $i++;
                                            } ?>
                                        </tbody>
                                    </table>
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>

    <script>
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

        function ConfirmDelete(PK_USER) {
            var conf = confirm("Are you sure you want to delete?");
            if (conf) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {
                        FUNCTION_NAME: 'deleteCustomer',
                        PK_USER: PK_USER
                    },
                    success: function(data) {
                        window.location.href = `all_customers.php`;
                    }
                });
            }
        }

        function editpage(id, master_id) {
            window.location.href = "customer.php?id=" + id + "&master_id=" + master_id;

        }
    </script>
</body>

</html>