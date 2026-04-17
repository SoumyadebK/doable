<?php
require_once('../global/config.php');
$title = "All Leads";

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

// Date range filter logic
$start_date = isset($_GET['start_date']) && $_GET['start_date'] != '' ? date('Y-m-d', strtotime($_GET['start_date'])) : '';
$end_date = isset($_GET['end_date']) && $_GET['end_date'] != '' ? date('Y-m-d', strtotime($_GET['end_date'])) : '';

$date_range_condition = '';
if ($start_date && $end_date) {
    $date_range_condition = " AND DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'";
} elseif ($start_date) {
    $date_range_condition = " AND DATE = '" . $start_date . "'";
} elseif ($end_date) {
    $date_range_condition = " AND DATE = '" . $end_date . "'";
}

$status_check = empty($_GET['status']) ? '' : $_GET['status'];

$status_condition = ' ';
if ($status_check != '') {
    $status_condition = " AND DOA_LEADS.PK_LEAD_STATUS = " . $status_check;
}

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '') {
    header("location:../login.php");
    exit;
}

$results_per_page = 100;

if (isset($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = " AND (DOA_LEADS.FIRST_NAME LIKE '%" . $search_text . "%' OR DOA_LEADS.LAST_NAME LIKE '%" . $search_text . "%' OR DOA_LEADS.PHONE LIKE '%" . $search_text . "%' OR DOA_LEADS.EMAIL_ID LIKE '%" . $search_text . "%' OR LS.LEAD_STATUS LIKE '%" . $search_text . "%')";
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
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

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

    .filter-row {
        margin-bottom: 15px;
    }

    .date-range-group {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .clear-filter-btn {
        margin-left: 10px;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 0px !important;">

            <div class="container-fluid body_content" style="margin-top: 0px;">
                <div class="row page-titles">
                    <div class="col-md-3 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-6">
                        <form id="search_form" class="form-material form-horizontal" action="" method="get">
                            <div class="row filter-row">
                                <div class="col-md-5 align-self-center text-end">
                                    <div class="date-range-group">
                                        <input type="text" id="start_date" name="start_date" class="form-control datepicker-normal" placeholder="Start Date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                                        <span>to</span>
                                        <input type="text" id="end_date" name="end_date" class="form-control datepicker-normal" placeholder="End Date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
                                        <?php if (!empty($_GET['start_date']) || !empty($_GET['end_date'])): ?>
                                            <button type="button" class="btn btn-warning btn-sm clear-filter-btn" onclick="clearDateRange()">Clear</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-3 align-self-center text-end">
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
                                <div class="col-md-4 align-self-center text-end">
                                    <div class="input-group">
                                        <input class="form-control" type="text" name="search_text" placeholder="Search.." value="<?= htmlspecialchars($search_text) ?>">
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
                                        // If a specific status is selected from dropdown, only show that status column
                                        $status_filter_condition = '';
                                        if (!empty($status_check)) {
                                            $status_filter_condition = " AND DOA_LEADS.PK_LEAD_STATUS = " . $status_check;
                                        }

                                        $leads_status = $db->Execute("SELECT * FROM `DOA_LEAD_STATUS` WHERE ACTIVE = 1 AND (`PK_ACCOUNT_MASTER` = " . $_SESSION['PK_ACCOUNT_MASTER'] . ") ORDER BY DISPLAY_ORDER ASC");

                                        // If status filter is applied, only show that specific column
                                        if (!empty($status_check)) {
                                            // Filter the statuses to only show the selected one
                                            $filtered_statuses = array();
                                            while (!$leads_status->EOF) {
                                                if ($leads_status->fields['PK_LEAD_STATUS'] == $status_check) {
                                                    $filtered_statuses[] = $leads_status->fields;
                                                }
                                                $leads_status->MoveNext();
                                            }
                                            $leads_status = $filtered_statuses;
                                        } else {
                                            // Convert to array for consistent handling
                                            $all_statuses = array();
                                            while (!$leads_status->EOF) {
                                                $all_statuses[] = $leads_status->fields;
                                                $leads_status->MoveNext();
                                            }
                                            $leads_status = $all_statuses;
                                        }

                                        foreach ($leads_status as $status_data) {
                                            // Get leads with their latest follow-up date only
                                            $leds_user = $db->Execute(
                                                "
            SELECT DISTINCT 
                DOA_LEADS.PK_LEADS, 
                CONCAT(DOA_LEADS.FIRST_NAME, ' ', DOA_LEADS.LAST_NAME) AS NAME, 
                DOA_LEADS.PHONE, 
                DOA_LEADS.EMAIL_ID, 
                LS.LEAD_STATUS, 
                DOA_LEADS.DESCRIPTION, 
                DOA_LEADS.OPPORTUNITY_SOURCE, 
                DOA_LEADS.ACTIVE, 
                DOA_LEADS.CREATED_ON, 
                DOA_LEADS.IS_CALLED, 
                DOA_LEADS.IS_APPOINTMENT_CREATED, 
                DOA_LOCATION.LOCATION_NAME,
                (SELECT DATE FROM DOA_LEAD_DATE 
                 WHERE PK_LEADS = DOA_LEADS.PK_LEADS 
                 ORDER BY CREATED_ON DESC 
                 LIMIT 1) AS LATEST_DATE
            FROM `DOA_LEADS` 
            INNER JOIN " . $master_database . ".DOA_LOCATION AS DOA_LOCATION 
                ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION 
            LEFT JOIN DOA_LEAD_STATUS AS LS 
                ON DOA_LEADS.PK_LEAD_STATUS = LS.PK_LEAD_STATUS 
            WHERE DOA_LEADS.PK_LEAD_STATUS = " . $status_data['PK_LEAD_STATUS'] . " 
                AND DOA_LEADS.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") 
                AND DOA_LEADS.ACTIVE = 1" . $search
                                            );

                                            // If date range filter is applied, we need to filter the results
                                            $filtered_leads = array();
                                            if ($start_date || $end_date) {
                                                while (!$leds_user->EOF) {
                                                    $lead_date = $leds_user->fields['LATEST_DATE'];
                                                    $include_lead = false;

                                                    if ($lead_date && $lead_date != '0000-00-00') {
                                                        if ($start_date && $end_date) {
                                                            if ($lead_date >= $start_date && $lead_date <= $end_date) {
                                                                $include_lead = true;
                                                            }
                                                        } elseif ($start_date) {
                                                            if ($lead_date >= $start_date) {
                                                                $include_lead = true;
                                                            }
                                                        } elseif ($end_date) {
                                                            if ($lead_date <= $end_date) {
                                                                $include_lead = true;
                                                            }
                                                        }
                                                    }

                                                    if ($include_lead) {
                                                        $filtered_leads[] = $leds_user->fields;
                                                    }
                                                    $leds_user->MoveNext();
                                                }
                                                $lead_count = count($filtered_leads);
                                            } else {
                                                $lead_count = $leds_user->RecordCount();
                                            }
                                        ?>

                                            <div class="kanban-column">
                                                <div class="kanban-header" style="background: <?= ($status_data['STATUS_COLOR'] == '') ? '#a9a9a947' : $status_data['STATUS_COLOR'] ?>;">
                                                    <?= $status_data['LEAD_STATUS'] ?><br>
                                                    <small><?= $lead_count ?> Opportunities</small>
                                                </div>
                                                <div class="kanban-body">
                                                    <?php
                                                    if ($start_date || $end_date) {
                                                        // Display filtered leads
                                                        foreach ($filtered_leads as $lead) {
                                                    ?>
                                                            <div class="kanban-card">
                                                                <div style="float: right;">
                                                                    <?php if ($lead['IS_APPOINTMENT_CREATED']) { ?>
                                                                        <i class="fas fa-star" style="color: gold;"></i>
                                                                    <?php } ?>

                                                                    <?php if ($lead['IS_CALLED']) { ?>
                                                                        <i class="fas fa-check-square" style="color: #39b54a;"></i>
                                                                    <?php } ?>
                                                                    <a href="javascript:;" onclick="ConfirmDelete(<?= $lead['PK_LEADS'] ?>);" title="Delete" style="color: red;"><i class="fa fa-trash"></i></a>
                                                                </div>
                                                                <div class="title" onclick="editpage(<?= $lead['PK_LEADS'] ?>, '<?= $lead['LATEST_DATE'] ?? '' ?>', '<?= htmlspecialchars($start_date) ?>', '<?= htmlspecialchars($end_date) ?>', '<?= htmlspecialchars($_GET['status'] ?? '') ?>', '<?= htmlspecialchars($_GET['search_text'] ?? '') ?>', '<?= htmlspecialchars($_GET['page'] ?? '') ?>');" style="cursor: pointer;">
                                                                    <?= $lead['NAME'] ?>
                                                                </div>
                                                                <div><strong>Source:</strong> <?= $lead['OPPORTUNITY_SOURCE'] ?></div>
                                                                <div><strong>Follow up Date:</strong>
                                                                    <?php
                                                                    if (!empty($lead['LATEST_DATE']) && $lead['LATEST_DATE'] != '0000-00-00') {
                                                                        echo date('m/d/Y', strtotime($lead['LATEST_DATE']));
                                                                    } else {
                                                                        echo 'N/A';
                                                                    }
                                                                    ?>
                                                                </div>
                                                                <div class="kanban-icons">
                                                                    <div class="icon-with-pill">
                                                                        <i class="fas fa-phone toggle-pill" data-target="pill-phone-<?= $lead['PK_LEADS'] ?>"></i>
                                                                        <span class="pill pill-phone-<?= $lead['PK_LEADS'] ?>"><?= $lead['PHONE'] ?></span>
                                                                    </div>
                                                                    <div class="icon-with-pill">
                                                                        <i class="fas fa-envelope toggle-pill" data-target="pill-email-<?= $lead['PK_LEADS'] ?>"></i>
                                                                        <span class="pill pill-email-<?= $lead['PK_LEADS'] ?>"><?= $lead['EMAIL_ID'] ?></span>
                                                                    </div>
                                                                    <div class="icon-with-pill">
                                                                        <i class="fas fa-comment-dots toggle-pill" data-target="pill-chat-<?= $lead['PK_LEADS'] ?>"></i>
                                                                        <span class="pill pill-chat-<?= $lead['PK_LEADS'] ?>"><?= $lead['DESCRIPTION'] ?></span>
                                                                    </div>
                                                                    <div class="icon-with-pill">
                                                                        <i class="fas fa-calendar-alt toggle-pill" data-target="pill-calendar-<?= $lead['PK_LEADS'] ?>"></i>
                                                                        <span class="pill pill-calendar-<?= $lead['PK_LEADS'] ?>"><?= date('m/d/Y - h:iA', strtotime($lead['CREATED_ON'])) ?></span>
                                                                    </div>
                                                                    <div class="icon-with-pill" style="font-size: 22px;">
                                                                        <a href="javascript:;" onclick="callToLeads(<?= $lead['PK_LEADS'] ?>)" title="AI Call"><i class="fas fas fa-phone-square-alt"></i></a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php
                                                        }
                                                    } else {
                                                        while (!$leds_user->EOF) {
                                                        ?>
                                                            <div class="kanban-card">
                                                                <div style="float: right;">
                                                                    <?php if ($leds_user->fields['IS_APPOINTMENT_CREATED']) { ?>
                                                                        <i class="fas fa-star" style="color: gold;"></i>
                                                                    <?php } ?>

                                                                    <?php if ($leds_user->fields['IS_CALLED']) { ?>
                                                                        <i class="fas fa-check-square" style="color: #39b54a;"></i>
                                                                    <?php } ?>
                                                                    <a href="javascript:;" onclick="ConfirmDelete(<?= $leds_user->fields['PK_LEADS'] ?>);" title="Delete" style="color: red;"><i class="fa fa-trash"></i></a>
                                                                </div>
                                                                <div class="title" onclick="editpage(<?= $leds_user->fields['PK_LEADS'] ?>, '<?= $leds_user->fields['LATEST_DATE'] ?? '' ?>', '<?= htmlspecialchars($start_date) ?>', '<?= htmlspecialchars($end_date) ?>', '<?= htmlspecialchars($_GET['status'] ?? '') ?>', '<?= htmlspecialchars($_GET['search_text'] ?? '') ?>', '<?= htmlspecialchars($_GET['page'] ?? '') ?>');" style="cursor: pointer;">
                                                                    <?= $leds_user->fields['NAME'] ?>
                                                                </div>
                                                                <div><strong>Source:</strong> <?= $leds_user->fields['OPPORTUNITY_SOURCE'] ?></div>
                                                                <div><strong>Follow up Date:</strong>
                                                                    <?php
                                                                    if (!empty($leds_user->fields['LATEST_DATE']) && $leds_user->fields['LATEST_DATE'] != '0000-00-00') {
                                                                        echo date('m/d/Y', strtotime($leds_user->fields['LATEST_DATE']));
                                                                    } else {
                                                                        echo 'N/A';
                                                                    }
                                                                    ?>
                                                                </div>
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
                                                    <?php
                                                            $leds_user->MoveNext();
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        <?php
                                        }
                                        ?>

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
        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
            autoclose: true
        });

        $(document).ready(function() {
            $('.toggle-pill').on('click', function() {
                const target = $(this).data('target');
                $(this).closest('.kanban-card').find('.pill').not('.' + target).removeClass('show');
                $('.' + target).toggleClass('show');
            });

            // Auto-submit form when date range changes
            $('#start_date, #end_date').on('change', function() {
                $('#search_form').submit();
            });
        });

        $(function() {
            $('#myTable').DataTable();
        });

        function clearDateRange() {
            window.location.href = window.location.pathname + '?' +
                (new URLSearchParams(window.location.search).has('status') ? 'status=' + new URLSearchParams(window.location.search).get('status') + '&' : '') +
                (new URLSearchParams(window.location.search).has('search_text') ? 'search_text=' + new URLSearchParams(window.location.search).get('search_text') : '');
        }

        function editpage(id, date, start_date, end_date, filter_status, filter_search, filter_page) {
            let url = "leads.php?id=" + id + "&date=" + encodeURIComponent(date);

            // Add filter parameters to preserve them when going to leads.php
            if (start_date && start_date !== '') {
                url += "&filter_start_date=" + encodeURIComponent(start_date);
            }
            if (end_date && end_date !== '') {
                url += "&filter_end_date=" + encodeURIComponent(end_date);
            }
            if (filter_status && filter_status !== '') {
                url += "&filter_status=" + encodeURIComponent(filter_status);
            }
            if (filter_search && filter_search !== '') {
                url += "&filter_search=" + encodeURIComponent(filter_search);
            }
            if (filter_page && filter_page !== '') {
                url += "&filter_page=" + encodeURIComponent(filter_page);
            }

            window.location.href = url;
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
                            // Preserve current filters after delete
                            let url = 'all_leads.php';
                            let params = [];

                            <?php if (!empty($start_date)): ?>
                                params.push('start_date=<?= urlencode($start_date) ?>');
                            <?php endif; ?>

                            <?php if (!empty($end_date)): ?>
                                params.push('end_date=<?= urlencode($end_date) ?>');
                            <?php endif; ?>

                            <?php if (!empty($_GET['status'])): ?>
                                params.push('status=<?= urlencode($_GET['status']) ?>');
                            <?php endif; ?>

                            <?php if (!empty($_GET['search_text'])): ?>
                                params.push('search_text=<?= urlencode($_GET['search_text']) ?>');
                            <?php endif; ?>

                            <?php if (!empty($_GET['page'])): ?>
                                params.push('page=<?= urlencode($_GET['page']) ?>');
                            <?php endif; ?>

                            if (params.length > 0) {
                                url += '?' + params.join('&');
                            }

                            window.location.href = url;
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