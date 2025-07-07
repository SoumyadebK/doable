<?php
require_once('../global/config.php');
$title = "All Leads";

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$status_check = empty($_GET['status']) ? 'active' : $_GET['status'];

if ($status_check == 'active') {
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '') {
    header("location:../login.php");
    exit;
}

$results_per_page = 100;

if (isset($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = " AND (DOA_LEADS.FIRST_NAME LIKE '%" . $search_text . "%' OR DOA_LEADS.LAST_NAME LIKE '%" . $search_text . "%' OR DOA_LEADS.PHONE LIKE '%" . $search_text . "%' OR DOA_LEADS.EMAIL_ID LIKE '%" . $search_text . "%' OR DOA_LEAD_STATUS.LEAD_STATUS LIKE '%" . $search_text . "%')";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db->Execute("SELECT count(DOA_LEADS.PK_LEADS) AS TOTAL_RECORDS FROM DOA_LEADS");
$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil($number_of_result / $results_per_page);

if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = $_GET['page'];
}

$page_first_result = ($page - 1) * $results_per_page;
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
            <?php require_once('../includes/setup_menu.php') ?>
            <div class="container-fluid body_content m-0">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
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
                    <div class="col-md-4 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='leads.php'"><i class="fa fa-plus-circle"></i> Create New</button>
                        </div>
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
                                                <th>No</th>
                                                <th>Name</th>
                                                <th>Phone</th>
                                                <th>Email</th>
                                                <th>Location</th>
                                                <th>Lead Status</th>
                                                <th>Description</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                            $i = 1;
                                            $row = $db->Execute("SELECT DOA_LEADS.PK_LEADS, CONCAT(DOA_LEADS.FIRST_NAME, ' ', DOA_LEADS.LAST_NAME) AS NAME, DOA_LEADS.PHONE, DOA_LEADS.EMAIL_ID, DOA_LEAD_STATUS.LEAD_STATUS, DOA_LEADS.DESCRIPTION, DOA_LEADS.ACTIVE, DOA_LOCATION.LOCATION_NAME FROM `DOA_LEADS` INNER JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION LEFT JOIN DOA_LEAD_STATUS ON DOA_LEADS.PK_LEAD_STATUS = DOA_LEAD_STATUS.PK_LEAD_STATUS WHERE DOA_LEADS.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_LEADS.ACTIVE = 1" . $search . " LIMIT " . $page_first_result . ',' . $results_per_page);
                                            while (!$row->EOF) { ?>
                                                <tr>
                                                    <td onclick="editpage(<?= $row->fields['PK_LEADS'] ?>);"><?= $i; ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_LEADS'] ?>);"><?= $row->fields['NAME'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_LEADS'] ?>);"><?= $row->fields['PHONE'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_LEADS'] ?>);"><?= $row->fields['EMAIL_ID'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_LEADS'] ?>);"><?= $row->fields['LOCATION_NAME'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_LEADS'] ?>);"><?= $row->fields['LEAD_STATUS'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_LEADS'] ?>);"><?= $row->fields['DESCRIPTION'] ?></td>
                                                    <td>
                                                        <?php if ($row->fields['ACTIVE'] == 1) { ?>
                                                            <span class="active-box-green"></span>
                                                        <?php } else { ?>
                                                            <span class="active-box-red"></span>
                                                        <?php } ?>
                                                        &nbsp;&nbsp;&nbsp;&nbsp;
                                                        <a href="leads.php?id=<?= $row->fields['PK_LEADS'] ?>" title="Edit" style="font-size:18px"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <a href="javascript:" onclick="ConfirmDelete(<?= $row->fields['PK_LEADS'] ?>);" title="Delete" style="font-size:18px"><i class="fa fa-trash"></i></a>
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
                                                    <li><a href="all_leads.php?status=<?= $status_check ?>&page=1">&laquo;</a></li>
                                                    <li><a href="all_leads.php?status=<?= $status_check ?>&page=<?= ($page - 1) ?>">&lsaquo;</a></li>
                                                <?php }
                                                for ($page_count = 1; $page_count <= $number_of_page; $page_count++) {
                                                    if ($page_count == $page || $page_count == ($page + 1) || $page_count == ($page - 1) || $page_count == $number_of_page) {
                                                        echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="all_leads.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                    } elseif ($page_count == ($number_of_page - 1)) {
                                                        echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                    } else {
                                                        echo '<li><a class="hidden" href="all_leads.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                    }
                                                }
                                                if ($page < $number_of_page) { ?>
                                                    <li><a href="all_leads.php?status=<?= $status_check ?>&page=<?= ($page + 1) ?>">&rsaquo;</a></li>
                                                    <li><a href="all_leads.php?status=<?= $status_check ?>&page=<?= $number_of_page ?>">&raquo;</a></li>
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
        $(function() {
            $('#myTable').DataTable();
        });

        function editpage(id) {
            //alert(i);
            window.location.href = "leads.php?id=" + id;
        }

        function ConfirmDelete(PK_LEADS) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deleteLeads',
                            PK_LEADS: PK_LEADS
                        },
                        success: function(data) {
                            let currentURL = window.location.href;
                            let extractedPart = currentURL.substring(currentURL.lastIndexOf("/") + 1);
                            console.log(extractedPart);
                            window.location.href = extractedPart;
                        }
                    });
                }
            });
        }
    </script>
</body>

</html>