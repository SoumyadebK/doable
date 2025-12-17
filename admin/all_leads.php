<?php
require_once('../global/config.php');
$title = "All Leads";

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$status_check = empty($_GET['status']) ? '' : $_GET['status'];

$status_condition = ' ';
if ($status_check != '') {
    $status_condition = " AND PK_LEAD_STATUS = " . $status_check;
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

$lead_status = ['New' => '#fffbb9', 'Enrolled' => '#96d35f', 'Not Enrolled' => '#ffa57d'];
$i = 1;
foreach ($lead_status as $key => $value) {
    $is_exist = $db->Execute("SELECT * FROM DOA_LEAD_STATUS WHERE LEAD_STATUS='" . $key . "' AND PK_ACCOUNT_MASTER='" . $_SESSION['PK_ACCOUNT_MASTER'] . "'");
    if ($is_exist->RecordCount() == 0) {
        $lead_status_data['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $lead_status_data['LEAD_STATUS'] = $key;
        $lead_status_data['STATUS_COLOR'] = $value;
        $lead_status_data['DISPLAY_ORDER'] = $i;
        $lead_status_data['ACTIVE'] = 1;
        db_perform('DOA_LEAD_STATUS', $lead_status_data, 'insert');
    }
    $i++;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

<style>
    body {
        overflow-x: auto;
    }

    .kanban-board {
        display: flex;
        flex-wrap: nowrap;
        min-width: max-content;
        padding: 20px;
    }

    .kanban-column {
        flex: 0 0 300px;
        margin-right: 15px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        display: flex;
        flex-direction: column;
        max-height: 80vh;
    }

    .kanban-header {
        color: black;
        padding: 10px;
        font-weight: bold;
        text-align: center;
    }

    .kanban-body {
        overflow-y: auto;
        padding: 10px;
        flex: 1;
    }

    .kanban-card {
        background: white;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-bottom: 10px;
        padding: 10px;
    }

    .kanban-card .title {
        font-weight: bold;
        margin-bottom: 5px;
    }

    .kanban-card .title:hover {
        color: #39b54a;
    }




    .kanban-icons {
        display: flex;
        gap: 15px;
        align-items: center;
        flex-wrap: nowrap;
        margin-top: 15px;
    }

    .icon-with-pill {
        display: flex;
        align-items: center;
        position: relative;
    }

    .icon-with-pill i {
        color: #39b54a;
        font-size: 15px;
        cursor: pointer;
    }

    /* Pill initially hidden */
    .pill {
        background-color: #eefdf0ff;
        color: #39b54a;
        font-size: 0.75rem;
        font-weight: 500;
        padding: 4px 10px;
        border-radius: 20px;
        white-space: nowrap;
        margin-left: 6px;

        opacity: 0;
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.5s ease, opacity 0.5s ease;
        display: none;
    }

    /* Pill visible */
    .pill.show {
        display: inline-block;
        opacity: 1;
        transform: scaleX(1);
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <?php require_once('../includes/setup_menu.php') ?>
            <div class="container-fluid body_content m-0">
                <div class="row page-titles">
                    <div class="col-md-3 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-6">
                        <form class=" form-material form-horizontal" action="" method="get">
                            <div class="row">
                                <div class="col-md-4 align-self-center text-end">
                                    <select class="form-control" name="status" id="status" onchange="this.form.submit();">
                                        <option value="">Select Status</option>
                                        <?php
                                        $row = $db->Execute("SELECT * FROM `DOA_LEAD_STATUS` WHERE ACTIVE = 1 AND `PK_ACCOUNT_MASTER` = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DISPLAY_ORDER ASC");
                                        while (!$row->EOF) { ?>
                                            <option value="<?php echo $row->fields['PK_LEAD_STATUS']; ?>" <?= ($row->fields['PK_LEAD_STATUS'] == $status_check) ? 'selected' : '' ?>><?= $row->fields['LEAD_STATUS'] ?></option>
                                        <?php $row->MoveNext();
                                        } ?>
                                    </select>
                                </div>

                                <div class=" col-md-8 align-self-center text-end">
                                    <div class="input-group">
                                        <input class="form-control" type="text" name="search_text" placeholder="Search.." value="<?= $search_text ?>">
                                        <button class="btn btn-info waves-effect waves-light m-r-10 text-white input-group-btn m-b-1" type="submit"><i class="fa fa-search"></i></button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-3 align-self-center text-end">
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

                                    <div class="kanban-board">
                                        <?php
                                        $leads_status = $db->Execute("SELECT * FROM `DOA_LEAD_STATUS` WHERE ACTIVE = 1 AND (`PK_ACCOUNT_MASTER` = " . $_SESSION['PK_ACCOUNT_MASTER'] . ") $status_condition ORDER BY DISPLAY_ORDER ASC");
                                        while (!$leads_status->EOF) {
                                            $leds_user = $db->Execute("SELECT DOA_LEADS.PK_LEADS, CONCAT(DOA_LEADS.FIRST_NAME, ' ', DOA_LEADS.LAST_NAME) AS NAME, DOA_LEADS.PHONE, DOA_LEADS.EMAIL_ID, DOA_LEAD_STATUS.LEAD_STATUS, DOA_LEADS.DESCRIPTION, DOA_LEADS.OPPORTUNITY_SOURCE, DOA_LEADS.ACTIVE, DOA_LEADS.CREATED_ON, DOA_LOCATION.LOCATION_NAME FROM `DOA_LEADS` INNER JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION LEFT JOIN DOA_LEAD_STATUS ON DOA_LEADS.PK_LEAD_STATUS = DOA_LEAD_STATUS.PK_LEAD_STATUS WHERE DOA_LEADS.PK_LEAD_STATUS = " . $leads_status->fields['PK_LEAD_STATUS'] . " AND DOA_LEADS.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_LEADS.ACTIVE = 1" . $search); ?>
                                            <div class="kanban-column">
                                                <div class="kanban-header" style="background: <?= ($leads_status->fields['STATUS_COLOR'] == '') ? '#a9a9a947' : $leads_status->fields['STATUS_COLOR'] ?>;"><?= $leads_status->fields['LEAD_STATUS'] ?><br><small><?= $leds_user->RecordCount(); ?> Opportunities</small></div>
                                                <div class="kanban-body">
                                                    <?php while (!$leds_user->EOF) { ?>
                                                        <div class="kanban-card">
                                                            <div style="float: right;"><a href="javascript:;" onclick="ConfirmDelete(<?= $leds_user->fields['PK_LEADS'] ?>);" title="Delete" style="color: red;"><i class="fa fa-trash"></i></a></div>
                                                            <div class="title" onclick="editpage(<?= $leds_user->fields['PK_LEADS'] ?>);" style="cursor: pointer;">
                                                                <?= $leds_user->fields['NAME'] ?>
                                                            </div>
                                                            <div><strong>Source:</strong> <?= $leds_user->fields['OPPORTUNITY_SOURCE'] ?></div>
                                                            <div class="kanban-icons">
                                                                <div class="icon-with-pill">
                                                                    <i class="fas fa-phone toggle-pill" data-target="pill-phone-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                                    <span class="pill pill-phone-<?= $leds_user->fields['PK_LEADS'] ?>"><?= $leds_user->fields['PHONE'] ?></span>
                                                                </div>
                                                                <div class="icon-with-pill">
                                                                    <i class="fas fa-envelope toggle-pill" data-target="pill-email-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                                    <span class="pill pill-email-<?= $leds_user->fields['PK_LEADS'] ?>"><?= $leds_user->fields['EMAIL_ID'] ?></span>
                                                                </div>
                                                                <div class="icon-with-pill">
                                                                    <i class="fas fa-comment-dots toggle-pill" data-target="pill-chat-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                                    <span class="pill pill-chat-<?= $leds_user->fields['PK_LEADS'] ?>"><?= $leds_user->fields['DESCRIPTION'] ?></span>
                                                                </div>
                                                                <div class="icon-with-pill">
                                                                    <i class="fas fa-calendar-alt toggle-pill" data-target="pill-calendar-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                                    <span class="pill pill-calendar-<?= $leds_user->fields['PK_LEADS'] ?>"><?= date('m/d/Y - h:iA', strtotime($leds_user->fields['CREATED_ON'])) ?></span>
                                                                </div>

                                                                <div class="icon-with-pill" style="font-size: 22px;">
                                                                    <a href="javascript:;" onclick="callToLeads(<?= $leds_user->fields['PK_LEADS'] ?>)" title="AI Call"><i class="fas fas fa-phone-square-alt"></i></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php $leds_user->MoveNext();
                                                    } ?>
                                                </div>
                                            </div>
                                        <?php $leads_status->MoveNext();
                                        }

                                        $leds_user = $db->Execute("SELECT DOA_LEADS.PK_LEADS, CONCAT(DOA_LEADS.FIRST_NAME, ' ', DOA_LEADS.LAST_NAME) AS NAME, DOA_LEADS.PHONE, DOA_LEADS.EMAIL_ID, DOA_LEAD_STATUS.LEAD_STATUS, DOA_LEADS.DESCRIPTION, DOA_LEADS.OPPORTUNITY_SOURCE, DOA_LEADS.ACTIVE, DOA_LEADS.CREATED_ON, DOA_LOCATION.LOCATION_NAME FROM `DOA_LEADS` INNER JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION LEFT JOIN DOA_LEAD_STATUS ON DOA_LEADS.PK_LEAD_STATUS = DOA_LEAD_STATUS.PK_LEAD_STATUS WHERE DOA_LEADS.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_LEADS.ACTIVE = 0" . $search); ?>
                                        <div class="kanban-column">
                                            <div class="kanban-header" style="background: #e80c0cbd; color: white;">Inactive<br><small><?= $leds_user->RecordCount(); ?></small></div>
                                            <div class="kanban-body">
                                                <?php while (!$leds_user->EOF) { ?>
                                                    <div class="kanban-card">
                                                        <div style="float: right;"><a href="javascript:;" onclick="ConfirmDelete(<?= $leds_user->fields['PK_LEADS'] ?>);" title="Delete" style="color: red;"><i class="fa fa-trash"></i></a></div>
                                                        <div class="title" onclick="editpage(<?= $leds_user->fields['PK_LEADS'] ?>);" style="cursor: pointer;">
                                                            <?= $leds_user->fields['NAME'] ?>
                                                        </div>
                                                        <div><strong>Source:</strong> <?= $leds_user->fields['OPPORTUNITY_SOURCE'] ?></div>
                                                        <div class="kanban-icons">
                                                            <div class="icon-with-pill">
                                                                <i class="fas fa-phone toggle-pill" data-target="pill-phone-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-phone-<?= $leds_user->fields['PK_LEADS'] ?>"><?= $leds_user->fields['PHONE'] ?></span>
                                                            </div>
                                                            <div class="icon-with-pill">
                                                                <i class="fas fa-envelope toggle-pill" data-target="pill-email-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-email-<?= $leds_user->fields['PK_LEADS'] ?>"><?= $leds_user->fields['EMAIL_ID'] ?></span>
                                                            </div>
                                                            <div class="icon-with-pill">
                                                                <i class="fas fa-comment-dots toggle-pill" data-target="pill-chat-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-chat-<?= $leds_user->fields['PK_LEADS'] ?>"><?= $leds_user->fields['DESCRIPTION'] ?></span>
                                                            </div>
                                                            <div class="icon-with-pill">
                                                                <i class="fas fa-calendar-alt toggle-pill" data-target="pill-calendar-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-calendar-<?= $leds_user->fields['PK_LEADS'] ?>"><?= date('m/d/Y - h:iA', strtotime($leds_user->fields['CREATED_ON'])) ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php $leds_user->MoveNext();
                                                } ?>
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
    </div>
    <?php require_once('../includes/footer.php'); ?>
    <script>
        $(document).ready(function() {
            $('.toggle-pill').on('click', function() {
                const target = $(this).data('target');
                $(this).closest('.kanban-card').find('.pill').not('.' + target).removeClass('show');
                $('.' + target).toggleClass('show');
            });
        });

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
                            window.location.href = 'all_leads.php';
                        }
                    });
                }
            });
        }

        function callToLeads(PK_LEADS) {
            $.ajax({
                url: "../voice_agent/outbound_call.php",
                type: 'GET',
                data: {
                    PK_LEADS: PK_LEADS
                },
                success: function(response) {
                    if (response === 'success') {
                        Swal.fire(
                            'Call Initiated!',
                            'The call to the lead has been initiated successfully.',
                            'success'
                        );
                    } else {
                        Swal.fire(
                            'Error!',
                            response,
                            'error'
                        );
                    }
                },
                error: function() {
                    Swal.fire(
                        'Error!',
                        'There was an error initiating the call. Please try again.',
                        'error'
                    );
                }
            });
        }
    </script>
</body>

</html>