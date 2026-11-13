<?php
require_once('../global/config.php');
$title = "All Events";
global $db;
global $db_account;
global $master_database;
global $results_per_page;

$status_check = empty($_GET['status']) ? 'active' : $_GET['status'];

if ($status_check == 'active') {
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

$event_status = empty($_GET['event_status']) ? '1' : $_GET['event_status'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (isset($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = " AND DOA_EVENT.HEADER LIKE '%" . $search_text . "%' OR DOA_EVENT.START_DATE LIKE '%" . $search_text . "%' OR DOA_EVENT.START_TIME LIKE '%" . $search_text . "%'";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db_account->Execute("SELECT count(DOA_EVENT.PK_EVENT) AS TOTAL_RECORDS FROM `DOA_EVENT` JOIN DOA_EVENT_LOCATION ON DOA_EVENT.PK_EVENT = DOA_EVENT_LOCATION.PK_EVENT LEFT JOIN DOA_EVENT_TYPE ON DOA_EVENT.PK_EVENT_TYPE = DOA_EVENT_TYPE.PK_EVENT_TYPE WHERE DOA_EVENT_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_EVENT.PK_ACCOUNT_MASTER =" . $_SESSION['PK_ACCOUNT_MASTER'] . $search);
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
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>
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

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">

        <div class="page-wrapper" style="padding-top: 0px !important;">

            <div class="container-fluid body_content" style="margin-top: 0px;">
                <div class="row page-titles">
                    <div class="col-md-2 align-self-center">
                        <?php if ($status_check == 'inactive') { ?>
                            <h4 class="text-themecolor">Not Active Events</h4>
                        <?php } elseif ($status_check == 'active') { ?>
                            <h4 class="text-themecolor">Active Events</h4>
                        <?php } ?>
                    </div>

                    <?php if ($status_check == 'inactive') { ?>
                        <div class="col-md-3 align-self-center">
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_events.php?status=active'"><i class="fa fa-user"></i> Show Active</button>
                        </div>
                    <?php } elseif ($status_check == 'active') { ?>
                        <div class="col-md-3 align-self-center">
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_events.php?status=inactive'"><i class="fa fa-user-times"></i> Show Not Active</button>
                        </div>
                    <?php } ?>

                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <button type="button" style="margin-right: 78%;" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='event.php'"><i class="fa fa-plus-circle"></i> Create New</button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="height: 100px">
                        <div class="row">
                            <div class="col-2">
                                <div class="form-material form-horizontal">
                                    <label class="form-label">Event Type</label>
                                    <select class="form-control" name="PK_EVENT_TYPE" id="PK_EVENT_TYPE">
                                        <option value="">Select Event Type</option>
                                        <?php
                                        $row = $db_account->Execute("SELECT * FROM `DOA_EVENT_TYPE` WHERE PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " AND `ACTIVE` = 1");
                                        while (!$row->EOF) { ?>
                                            <option value="<?php echo $row->fields['EVENT_TYPE']; ?>"><?= $row->fields['EVENT_TYPE'] ?></option>
                                        <?php $row->MoveNext();
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-material form-horizontal">
                                    <label class="form-label">Event Status</label>
                                    <select class="form-control" name="PK_EVENT_STATUS" id="PK_EVENT_STATUS" onchange="selectStatus(this)">
                                        <option value="">Select Event Status</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-2">
                                <div class="form-material form-horizontal">
                                    <label class="form-label">From Date</label>
                                    <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" value="">
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-material form-horizontal">
                                    <label class="form-label">To Date</label>
                                    <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" required value="">
                                </div>
                            </div>
                            <div class="col-4">
                                <form class="form-material form-horizontal" action="" method="get">
                                    <label class="form-label">Search</label>
                                    <div class="input-group">
                                        <input class="form-control" type="text" name="search_text" placeholder="Search.." value="<?= $search_text ?>">
                                        <button class="btn btn-info waves-effect waves-light m-r-10 text-white input-group-btn m-b-1" type="submit"><i class="fa fa-search"></i></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped border" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                <th data-type="number" class="sortable" style="cursor: pointer">No</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Event Name</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Type</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Location</th>
                                                <th data-date data-order class="sortable" style="cursor: pointer">Start Date</th>
                                                <th data-type="time" class="sortable" style="cursor: pointer">Start Time</th>
                                                <th data-date data-order class="sortable" style="cursor: pointer">End Date</th>
                                                <th data-type="time" class="sortable" style="cursor: pointer">End Time</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                            $i = $page_first_result + 1;
                                            $row = $db_account->Execute("SELECT DISTINCT DOA_EVENT.*, DOA_EVENT_TYPE.EVENT_TYPE, DOA_LOCATION.LOCATION_NAME FROM `DOA_EVENT` JOIN DOA_EVENT_LOCATION ON DOA_EVENT.PK_EVENT = DOA_EVENT_LOCATION.PK_EVENT LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_EVENT_LOCATION.PK_LOCATION LEFT JOIN DOA_EVENT_TYPE ON DOA_EVENT.PK_EVENT_TYPE = DOA_EVENT_TYPE.PK_EVENT_TYPE WHERE DOA_EVENT_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_EVENT.ACTIVE='$status' AND DOA_EVENT.PK_ACCOUNT_MASTER =" . $_SESSION['PK_ACCOUNT_MASTER'] . $search . " ORDER BY DOA_EVENT.START_DATE DESC LIMIT " . $page_first_result . ',' . $results_per_page);
                                            while (!$row->EOF) { ?>
                                                <tr>
                                                    <td onclick="editpage(<?= $row->fields['PK_EVENT'] ?>);"><?= $i; ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_EVENT'] ?>);"><?= $row->fields['HEADER'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_EVENT'] ?>);"><?= $row->fields['EVENT_TYPE'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_EVENT'] ?>);"><?= $row->fields['LOCATION_NAME'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_EVENT'] ?>);"><?= date('m/d/Y', strtotime($row->fields['START_DATE'])) ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_EVENT'] ?>);"><?= date('h:i A', strtotime($row->fields['START_TIME'])) ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_EVENT'] ?>);"><?= ($row->fields['END_DATE'] == '0000-00-00') ? '' : date('m/d/Y', strtotime($row->fields['END_DATE'])) ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_EVENT'] ?>);"><?= ($row->fields['END_TIME'] == '00:00:00') ? '12:00 AM' : date('h:i A', strtotime($row->fields['END_TIME'])) ?></td>
                                                    <td>
                                                        <?php if (in_array('Events Edit', $PERMISSION_ARRAY)) { ?>
                                                            <a href="event.php?id=<?= $row->fields['PK_EVENT'] ?>" title="Edit" style="font-size:18px"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <?php } ?>
                                                        <?php if ($row->fields['ACTIVE'] == 1) { ?>
                                                            <span class="active-box-green"></span>
                                                            <span class="d-none">1</span>
                                                        <?php } else { ?>
                                                            <span class="active-box-red"></span>
                                                            <span class="d-none">0</span>
                                                        <?php } ?>
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
                                                    <li><a href="all_events.php?page=1">&laquo;</a></li>
                                                    <li><a href="all_events.php?page=<?= ($page - 1) ?>">&lsaquo;</a></li>
                                                <?php }
                                                for ($page_count = 1; $page_count <= $number_of_page; $page_count++) {
                                                    if ($page_count == $page || $page_count == ($page + 1) || $page_count == ($page - 1) || $page_count == $number_of_page) {
                                                        echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="all_events.php?page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                    } elseif ($page_count == ($number_of_page - 1)) {
                                                        echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                    } else {
                                                        echo '<li><a class="hidden" href="all_events.php?page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                    }
                                                }
                                                if ($page < $number_of_page) { ?>
                                                    <li><a href="all_events.php?page=<?= ($page + 1) ?>">&rsaquo;</a></li>
                                                    <li><a href="all_events.php?page=<?= $number_of_page ?>">&raquo;</a></li>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.10.11/sorting/date-eu.js"></script>

    <script>
        $(function() {
            startDate = $("#START_DATE").datepicker({
                changeMonth: true,
                changeYear: true,
                numberOfMonths: 1,
                onSelect: function(selected) {
                    $("#END_DATE").datepicker("option", "minDate", selected);
                    $("#START_DATE, #END_DATE").trigger("change");
                }
            });
            $("#END_DATE").datepicker({
                changeMonth: true,
                changeYear: true,
                numberOfMonths: 1,
                onSelect: function(selected) {
                    $("#START_DATE").datepicker("option", "maxDate", selected)
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
            window.location.href = "event.php?id=" + id;
        }

        function selectStatus(param) {
            var status = $(param).val();
            window.location.href = "all_events.php?event_status=" + status;
        }
    </script>
</body>

</html>