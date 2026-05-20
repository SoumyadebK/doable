<?php
require_once('../global/config.php');
global $db;
global $db_account;

if (empty($_GET['id']))
    $title = "Add Event";
else
    $title = "Edit Event";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    $EVENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $EVENT_DATA['HEADER'] = $_POST['HEADER'];
    $EVENT_DATA['PK_EVENT_TYPE'] = $_POST['PK_EVENT_TYPE'];
    $EVENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
    $EVENT_DATA['SHARE_WITH_CUSTOMERS'] = isset($_POST['SHARE_WITH_CUSTOMERS']) ? 1 : 0;
    $EVENT_DATA['SHARE_WITH_SERVICE_PROVIDERS'] = isset($_POST['SHARE_WITH_SERVICE_PROVIDERS']) ? 1 : 0;
    $EVENT_DATA['SHARE_WITH_EMPLOYEES'] = isset($_POST['SHARE_WITH_EMPLOYEES']) ? 1 : 0;
    $EVENT_DATA['START_DATE'] = date('Y-m-d', strtotime($_POST['START_DATE']));
    $EVENT_DATA['END_DATE'] = !empty($_POST['END_DATE']) ? date('Y-m-d', strtotime($_POST['END_DATE'])) : NULL;
    $EVENT_DATA['ALL_DAY'] = $_POST['ALL_DAY'] ?? 0;
    if ($EVENT_DATA['ALL_DAY'] == 1) {
        $EVENT_DATA['START_TIME'] = '00:00:00';
        $EVENT_DATA['END_TIME'] = '23:30:00';
    } else {
        $EVENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
        $EVENT_DATA['END_TIME'] = !empty($_POST['END_TIME']) ? date('H:i:s', strtotime($_POST['END_TIME'])) : NULL;
    }
    if (empty($_GET['id'])) {
        $EVENT_DATA['ACTIVE'] = 1;
        $EVENT_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $EVENT_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_EVENT', $EVENT_DATA, 'insert');
        $PK_EVENT = $db_account->insert_ID();
    } else {
        $EVENT_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $EVENT_DATA['EDITED_BY']    = $_SESSION['PK_USER'];
        $EVENT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_EVENT', $EVENT_DATA, 'update', " PK_EVENT =  '$_GET[id]'");
        $PK_EVENT = $_GET['id'];
    }

    $db_account->Execute("DELETE FROM `DOA_EVENT_LOCATION` WHERE `PK_EVENT` = '$PK_EVENT'");
    if (isset($_POST['PK_LOCATION'])) {
        $PK_LOCATION = $_POST['PK_LOCATION'];
        for ($i = 0; $i < count($PK_LOCATION); $i++) {
            $EVENT_LOCATION_DATA['PK_EVENT'] = $PK_EVENT;
            $EVENT_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION[$i];
            db_perform_account('DOA_EVENT_LOCATION', $EVENT_LOCATION_DATA, 'insert');
        }
    }

    $db_account->Execute("DELETE FROM `DOA_EVENT_IMAGE` WHERE `PK_EVENT` = '$PK_EVENT'");
    for ($i = 0; $i < count($_FILES['IMAGE']['name']); $i++) {
        $EVENT_IMAGE_DATA['PK_EVENT'] = $PK_EVENT;
        if (!empty($_FILES['IMAGE']['name'][$i])) {
            $extn             = explode(".", $_FILES['IMAGE']['name'][$i]);
            $iindex            = count($extn) - 1;
            $rand_string     = time() . "-" . rand(100000, 999999);
            $file11            = 'event_image_' . $PK_EVENT . '_' . $rand_string . "." . $extn[$iindex];
            $extension       = strtolower($extn[$iindex]);

            $image_path    = '../uploads/event_image/' . $file11;
            move_uploaded_file($_FILES['IMAGE']['tmp_name'][$i], $image_path);
            $EVENT_IMAGE_DATA['IMAGE'] = $image_path;
        } else {
            $EVENT_IMAGE_DATA['IMAGE'] = $_POST['IMAGE_PATH'][$i];
        }
        $EVENT_IMAGE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $EVENT_IMAGE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_EVENT_IMAGE', $EVENT_IMAGE_DATA, 'insert');
    }
    header("location:events_list.php");
}

if (empty($_GET['id'])) {
    $HEADER = '';
    $PK_EVENT_TYPE = '';
    $START_DATE = '';
    $START_TIME = '';
    $ALL_DAY = '';
    $END_DATE = '0000-00-00';
    $END_TIME = '00:00:00';
    $DESCRIPTION = '';
    $PK_LOCATION = '';
    $SHARE_WITH_CUSTOMERS = '';
    $SHARE_WITH_SERVICE_PROVIDERS = '';
    $SHARE_WITH_EMPLOYEES = '';
    $ACTIVE = '';
} else {
    $res = $db_account->Execute("SELECT * FROM `DOA_EVENT` WHERE `PK_EVENT` = '$_GET[id]'");

    if ($res->RecordCount() == 0) {
        header("location:all_events.php");
        exit;
    }

    $HEADER = $res->fields['HEADER'];
    $PK_EVENT_TYPE = $res->fields['PK_EVENT_TYPE'];
    $START_DATE = $res->fields['START_DATE'];
    $END_DATE = $res->fields['END_DATE'];
    $START_TIME = $res->fields['START_TIME'];
    $END_TIME = $res->fields['END_TIME'];
    $ALL_DAY = $res->fields['ALL_DAY'];
    $DESCRIPTION = $res->fields['DESCRIPTION'];
    $SHARE_WITH_CUSTOMERS = $res->fields['SHARE_WITH_CUSTOMERS'];
    $SHARE_WITH_SERVICE_PROVIDERS = $res->fields['SHARE_WITH_SERVICE_PROVIDERS'];
    $SHARE_WITH_EMPLOYEES = $res->fields['SHARE_WITH_EMPLOYEES'];
    $ACTIVE = $res->fields['ACTIVE'];
}

$location_operational_hour = $db->Execute("SELECT $account_database.DOA_OPERATIONAL_HOUR.OPEN_TIME, $account_database.DOA_OPERATIONAL_HOUR.CLOSE_TIME FROM $account_database.DOA_OPERATIONAL_HOUR LEFT JOIN $master_database.DOA_LOCATION ON $account_database.DOA_OPERATIONAL_HOUR.PK_LOCATION = $master_database.DOA_LOCATION.PK_LOCATION WHERE $master_database.DOA_LOCATION.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND $account_database.DOA_OPERATIONAL_HOUR.CLOSED = 0 ORDER BY $master_database.DOA_LOCATION.PK_LOCATION LIMIT 1");
if ($location_operational_hour->RecordCount() > 0) {
    $OPEN_TIME = $location_operational_hour->fields['OPEN_TIME'];
    $CLOSE_TIME = $location_operational_hour->fields['CLOSE_TIME'];
} else {
    $OPEN_TIME = '00:00:00';
    $CLOSE_TIME = '23:59:00';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
    <link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            color: #333;
        }

        .toolbar-btn {
            border: 1px solid #dee2e6;
            background: #fff;
            border-radius: 8px;
            font-size: 0.85rem;
            padding: 6px 12px;
            color: #444;
            transition: all 0.2s;
        }

        .toolbar-btn:hover {
            background-color: #f8f9fa;
        }

        .icon-circle {
            width: 45px;
            height: 45px;
            text-align: center;
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .text-green {
            color: #39b54a;
        }

        .btn-success {
            background-color: #39b54a !important;
            border: none;
        }

        .btn-success:hover {
            background-color: #2e8e3c;
        }

        .btn-outline-success {
            border-color: #39b54a !important;

        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: #344054;
            margin-bottom: 6px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            font-size: 0.85rem;
            border: 1px solid #dee2e6;
            padding: 8px 12px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #39b54a;
            box-shadow: 0 0 0 0.2rem rgba(57, 181, 74, 0.25);
        }

        .card-modern {
            border: 1px solid #e9ecef;
            border-radius: 16px;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .share-card {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e9ecef;
        }

        .share-card h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: #333;
        }

        .share-card .form-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            margin-bottom: 12px;
        }

        .share-card input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #39b54a;
        }

        .image-upload-box {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fafbfc;
        }

        .image-upload-box:hover {
            border-color: #39b54a;
            background: #f8f9fa;
        }

        .image-preview {
            position: relative;
            display: inline-block;
            margin: 10px;
        }

        .image-preview img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .remove-image-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
            border: none;
        }

        .ck-editor__editable_inline {
            min-height: 250px;
        }

        .SumoSelect {
            width: 100%;
        }

        .SumoSelect>.CaptionCont {
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .time-picker,
        .datepicker-normal {
            cursor: pointer;
        }

        .breadcrumb-modern {
            background: transparent;
            padding: 0;
            margin: 0;
        }

        .breadcrumb-modern .breadcrumb-item a {
            color: #6c757d;
            text-decoration: none;
        }

        .breadcrumb-modern .breadcrumb-item.active {
            color: #39b54a;
        }

        hr {
            margin: 20px 0;
            border-color: #e9ecef;
        }
    </style>
    <?php include 'layout/header_script.php'; ?>
    <?php require_once('../includes/header.php'); ?>
    <?php include 'layout/header.php'; ?>
</head>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid body_content" style="margin-top: 0px;">
                <div class="container-fluid py-4 px-4 bg-white m-3 rounded border mx-auto">
                    <!-- Header Section -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <div class="p-2 bg-white border me-3 icon-circle"><i class="bi bi-calendar-event fs-4 text-green"></i></div>
                            <div>
                                <h4 class="mb-0 fw-bold"><?= $title ?></h4>
                                <p class="text-muted small mb-0"><?= empty($_GET['id']) ? 'Create a new event' : 'Update event details' ?></p>
                            </div>
                        </div>
                        <button class="btn btn-success border-0 rounded-pill px-3" onclick="window.location.href='all_events.php'">
                            <i class="bi bi-arrow-left me-1"></i> Back to Events
                        </button>
                    </div>

                    <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card-modern p-4">
                                    <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-green"></i>Event Details</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Event Header <span class="text-danger">*</span></label>
                                            <input type="text" id="HEADER" name="HEADER" class="form-control" required value="<?php echo htmlspecialchars($HEADER) ?>" placeholder="Enter event name">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Event Type</label>
                                            <select class="form-select" name="PK_EVENT_TYPE" id="PK_EVENT_TYPE">
                                                <option value="">Select Event Type</option>
                                                <?php
                                                $row = $db_account->Execute("SELECT * FROM `DOA_EVENT_TYPE` WHERE PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " AND `ACTIVE` = 1");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_EVENT_TYPE']; ?>" <?= ($PK_EVENT_TYPE == $row->fields['PK_EVENT_TYPE']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['EVENT_TYPE']) ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                            <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" required value="<?php echo ($START_DATE) ? date('m/d/Y', strtotime($START_DATE)) : '' ?>" placeholder="mm/dd/yyyy">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">End Date</label>
                                            <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" value="<?php echo ($END_DATE == '0000-00-00') ? '' : date('m/d/Y', strtotime($END_DATE)) ?>" placeholder="mm/dd/yyyy">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label d-flex align-items-center gap-2">
                                            <input type="checkbox" class="form-check-input all_day" name="ALL_DAY" value="1" onchange="checkAllDay(this)" <?= ($ALL_DAY == 1) ? 'checked' : '' ?>>
                                            <span>All Day Event</span>
                                        </label>
                                    </div>

                                    <div class="row time" style="display: <?= ($ALL_DAY == 1) ? 'none' : 'flex' ?>">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Start Time</label>
                                            <input type="text" id="START_TIME" name="START_TIME" class="form-control time-picker" value="<?php echo ($START_TIME && $START_TIME != '00:00:00') ? date('h:i A', strtotime($START_TIME)) : '' ?>" placeholder="Select time">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">End Time</label>
                                            <input type="text" id="END_TIME" name="END_TIME" class="form-control time-picker" value="<?php echo ($END_TIME && $END_TIME != '00:00:00') ? date('h:i A', strtotime($END_TIME)) : '' ?>" placeholder="Select time">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Location(s)</label>
                                        <select class="multi_sumo_select" name="PK_LOCATION[]" id="PK_LOCATION" multiple>
                                            <?php
                                            $selected_location = [];
                                            if (!empty($_GET['id'])) {
                                                $selected_location_row = $db_account->Execute("SELECT `PK_LOCATION` FROM `DOA_EVENT_LOCATION` WHERE `PK_EVENT` = '$_GET[id]'");
                                                while (!$selected_location_row->EOF) {
                                                    $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                                                    $selected_location_row->MoveNext();
                                                }
                                            }
                                            $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                            while (!$row->EOF) { ?>
                                                <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= in_array($row->fields['PK_LOCATION'], $selected_location) ? "selected" : "" ?>><?= htmlspecialchars($row->fields['LOCATION_NAME']) ?></option>
                                            <?php $row->MoveNext();
                                            } ?>
                                        </select>
                                    </div>

                                    <?php if (!empty($_GET['id'])) { ?>
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <div class="d-flex gap-4">
                                                <label class="d-flex align-items-center gap-2">
                                                    <input type="radio" name="ACTIVE" value="1" <?= ($ACTIVE == 1) ? 'checked' : '' ?>> Active
                                                </label>
                                                <label class="d-flex align-items-center gap-2">
                                                    <input type="radio" name="ACTIVE" value="0" <?= ($ACTIVE == 0) ? 'checked' : '' ?>> Inactive
                                                </label>
                                            </div>
                                        </div>
                                    <?php } ?>

                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="ckeditor" id="DESCRIPTION" name="DESCRIPTION"><?= htmlspecialchars($DESCRIPTION) ?></textarea>
                                    </div>
                                </div>

                                <!-- Images Section -->
                                <div class="card-modern p-4 mt-4">
                                    <h5 class="fw-bold mb-3"><i class="bi bi-images me-2 text-green"></i>Event Images</h5>
                                    <div id="add_more_image">
                                        <?php
                                        $hasImages = false;
                                        if (!empty($_GET['id'])) {
                                            $row = $db_account->Execute("SELECT * FROM DOA_EVENT_IMAGE WHERE PK_EVENT = " . $_GET['id']);
                                            if ($row->RecordCount() > 0) {
                                                $hasImages = true;
                                                while (!$row->EOF) { ?>
                                                    <div class="image-row mb-3" data-image-path="<?= htmlspecialchars($row->fields['IMAGE']) ?>">
                                                        <div class="row align-items-center">
                                                            <div class="col-4">
                                                                <div class="image-preview">
                                                                    <img src="<?= $row->fields['IMAGE'] ?>" alt="Event Image">
                                                                    <button type="button" class="remove-image-btn" onclick="removeImageRow(this)">×</button>
                                                                </div>
                                                                <input type="hidden" name="IMAGE_PATH[]" value="<?= $row->fields['IMAGE'] ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                            <?php
                                                    $row->MoveNext();
                                                }
                                            }
                                        }
                                        if (!$hasImages) { ?>
                                            <div class="image-row mb-3">
                                                <div class="row align-items-center">
                                                    <div class="col-4">
                                                        <div class="image-upload-box" onclick="$(this).closest('.row').find('input[type=file]').click()">
                                                            <i class="bi bi-cloud-upload fs-2 text-muted"></i>
                                                            <p class="small text-muted mb-0">Click to upload</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-8">
                                                        <input class="form-control d-none" type="file" name="IMAGE[]" accept="image/*" onchange="previewImage(this)">
                                                        <div class="image-preview-container"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <button type="button" class="btn btn-outline-success btn-sm mt-2" onclick="addMoreImages()">
                                        <i class="bi bi-plus-lg me-1"></i> Add More Images
                                    </button>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <!-- Share With Card -->
                                <div class="share-card">
                                    <h4><i class="bi bi-share me-2 text-green"></i>Share With</h4>
                                    <div class="form-group mb-3">
                                        <label class="form-label">
                                            <input type="checkbox" class="form-check-inline share_with" name="check_all" onchange="checkAll(this)" <?= ($SHARE_WITH_CUSTOMERS == 1 && $SHARE_WITH_SERVICE_PROVIDERS == 1 && $SHARE_WITH_EMPLOYEES == 1) ? 'checked' : '' ?>>
                                            <span>All (Customers, Providers & Employees)</span>
                                        </label>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">
                                            <input type="checkbox" class="form-check-inline share_with" name="SHARE_WITH_CUSTOMERS" value="1" <?= ($SHARE_WITH_CUSTOMERS == 1) ? 'checked' : '' ?>>
                                            <span>Customers</span>
                                        </label>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">
                                            <input type="checkbox" class="form-check-inline share_with" name="SHARE_WITH_SERVICE_PROVIDERS" value="1" <?= ($SHARE_WITH_SERVICE_PROVIDERS == 1) ? 'checked' : '' ?>>
                                            <span>Service Providers</span>
                                        </label>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">
                                            <input type="checkbox" class="form-check-inline share_with" name="SHARE_WITH_EMPLOYEES" value="1" <?= ($SHARE_WITH_EMPLOYEES == 1) ? 'checked' : '' ?>>
                                            <span>Employees</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Info Card -->
                                <div class="card-modern p-4 mt-4">
                                    <h5 class="fw-bold mb-3"><i class="bi bi-info-circle-fill me-2 text-green"></i>Information</h5>
                                    <p class="small text-muted mb-2"><i class="bi bi-clock me-2"></i> Events can be shared with customers, service providers, and employees.</p>
                                    <p class="small text-muted mb-0"><i class="bi bi-calendar me-2"></i> All day events will show without specific time slots.</p>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-3 justify-content-end mt-4">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" onclick="window.location.href='events_list.php'">
                                <i class="bi bi-x-lg me-1"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-success rounded-pill px-4">
                                <i class="bi bi-check-lg me-1"></i> <?= empty($_GET['id']) ? 'Create Event' : 'Update Event' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>
    <script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/34.2.0/classic/ckeditor.js"></script>

    <script>
        $(document).ready(function() {
            $("#START_DATE").datepicker({
                dateFormat: 'mm/dd/yy',
                changeMonth: true,
                changeYear: true,
                numberOfMonths: 1,
                onSelect: function(selected) {
                    $("#END_DATE").datepicker("option", "minDate", selected);
                }
            });
            $("#END_DATE").datepicker({
                dateFormat: 'mm/dd/yy',
                changeMonth: true,
                changeYear: true,
                numberOfMonths: 1,
                onSelect: function(selected) {
                    $("#START_DATE").datepicker("option", "maxDate", selected);
                }
            });

            let open_time = '<?= $OPEN_TIME ?>';
            let close_time = '<?= $CLOSE_TIME ?>';

            $('.time-picker').timepicker({
                timeFormat: 'h:i A',
                interval: 30,
                minTime: open_time,
                maxTime: close_time,
                dynamic: false,
                dropdown: true,
                scrollbar: true
            });

            $('.multi_sumo_select').SumoSelect({
                placeholder: 'Select Location(s)',
                selectAll: true,
                captionFormatAllSelected: 'All Locations Selected'
            });
        });

        function checkAll(ele) {
            var checkboxes = $('.share_with');
            var isChecked = $(ele).is(':checked');
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].type == 'checkbox' && checkboxes[i].name !== 'check_all') {
                    checkboxes[i].checked = isChecked;
                }
            }
        }

        function checkAllDay(all) {
            if (all.checked) {
                $('.time').slideUp();
            } else {
                $('.time').slideDown();
            }
        }

        ClassicEditor
            .create(document.querySelector('#DESCRIPTION'))
            .catch(error => {
                console.error(error);
            });

        function addMoreImages() {
            $('#add_more_image').append(`
                <div class="image-row mb-3">
                    <div class="row align-items-center">
                        <div class="col-4">
                            <div class="image-upload-box" onclick="$(this).closest('.row').find('input[type=file]').click()">
                                <i class="bi bi-cloud-upload fs-2 text-muted"></i>
                                <p class="small text-muted mb-0">Click to upload</p>
                            </div>
                        </div>
                        <div class="col-7">
                            <input class="form-control d-none" type="file" name="IMAGE[]" accept="image/*" onchange="previewImage(this)">
                            <div class="image-preview-container"></div>
                        </div>
                        <div class="col-1">
                            <button type="button" class="btn btn-link text-danger" onclick="removeImageRow(this)"><i class="bi bi-trash3"></i></button>
                        </div>
                    </div>
                </div>
            `);
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                var container = $(input).closest('.row').find('.image-preview-container');
                reader.onload = function(e) {
                    container.html(`<div class="image-preview mt-2"><img src="${e.target.result}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;"></div>`);
                }
                reader.readAsDataURL(input.files[0]);
                $(input).closest('.row').find('.image-upload-box').hide();
            }
        }

        function removeImageRow(btn) {
            $(btn).closest('.image-row').remove();
        }
    </script>
</body>

</html>