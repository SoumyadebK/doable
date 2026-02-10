<?php
$mail_url = parse_url($_SERVER['REQUEST_URI']);
$url_array = explode("/", $mail_url['path']);
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $current_address = $url_array[3];
} else {
    $current_address = $url_array[2];
}
?>
<style>
    .navbar-nav .nav-link {
        padding-right: 1.5rem !important;
        padding-left: 1.5rem !important;
        letter-spacing: 0.6px !important;
    }

    .navbar-nav .nav-link {
        position: relative;
    }

    .navbar-nav .nav-link.active {
        background-color: transparent !important;
        color: #fff !important;
        font-weight: 600 !important;
    }

    .navbar-nav .nav-link.active::after {
        content: "";
        position: absolute;
        left: 0;
        bottom: -8px;
        /* 👈 moved 20px down */
        width: 100%;
        height: 5px;
        background-color: #39b54a;
        border-radius: 2px;
    }

    .top-bar-icon {
        font-size: 21px;
        color: #fff;
    }

    .multi-select-dropdown {
        min-width: 280px;
        background-color: #ffffff;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        z-index: 1000;
    }

    .multi-select-header {
        padding: 12px 16px;
        border-bottom: 1px solid #e9ecef;
        font-size: 14px;
        font-weight: 500;
        color: #333;
    }

    .multi-select-options {
        max-height: 250px;
        overflow-y: auto;
        padding: 8px 0;
    }

    .multi-select-option {
        padding: 10px 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: background-color 0.2s ease;
    }

    .multi-select-option:hover {
        background-color: #f8f9fa;
    }

    .multi-select-option input[type="checkbox"] {
        cursor: pointer;
    }

    .multi-select-option label {
        cursor: pointer;
        margin: 0;
        flex: 1;
        font-size: 14px;
        color: #333;
    }

    .multi-select-footer {
        padding: 12px 16px;
        border-top: 1px solid #e9ecef;
        display: flex;
        gap: 8px;
    }

    .multi-select-footer button {
        flex: 1;
        padding: 8px 12px;
        font-size: 13px;
        border: 1px solid #e9ecef;
        background-color: #ffffff;
        color: #333;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .multi-select-footer button:hover {
        background-color: #f8f9fa;
    }

    .multi-select-footer button.apply-btn {
        background-color: #39b54a;
        color: #ffffff;
        border-color: #39b54a;
    }

    .multi-select-footer button.apply-btn:hover {
        background-color: #2fa03b;
    }

    .location-display-name {
        min-width: 150px;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: inline-block;
        text-align: center;
    }
</style>
<header class="app-topbar">
    <div class="container-fluid topbar-menu">
        <div class="d-flex align-items-center gap-2">
            <div id="user-dropdown-detailed" class="topbar-item py-3 border-end me-3 pe-2 brd-light">
                <div class="dropdown">



                    <?php
                    $selected_location = [];
                    if ($_SESSION["PK_ROLES"] == 2 || $_SESSION["PK_ROLES"] == 11) {
                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME, LOCATION_CODE FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                    } elseif ($_SESSION["PK_ROLES"] == 4) {
                        $selected_location_row = $db->Execute("SELECT `PRIMARY_LOCATION_ID` FROM `DOA_USER_MASTER` WHERE `PK_USER` = " . $_SESSION['PK_USER']);
                        while (!$selected_location_row->EOF) {
                            $selected_location[] = $selected_location_row->fields['PRIMARY_LOCATION_ID'];
                            $selected_location_row->MoveNext();
                        }
                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME, LOCATION_CODE FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_LOCATION IN (" . implode(',', $selected_location) . ")");
                    } else {
                        $selected_location_row = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` WHERE `PK_USER` = " . $_SESSION['PK_USER']);
                        while (!$selected_location_row->EOF) {
                            //echo $selected_location_row->fields['PK_LOCATION'];
                            $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                            $selected_location_row->MoveNext();
                        }
                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME, LOCATION_CODE FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_LOCATION IN (" . implode(',', $selected_location) . ")");
                    }
                    $selected_location_names = [];
                    $DEFAULT_LOCATION_ARRAY = explode(',', $_SESSION['DEFAULT_LOCATION_ID']);
                    foreach ($DEFAULT_LOCATION_ARRAY as $loc_id) {
                        $loc_row = $db->Execute("SELECT LOCATION_CODE FROM DOA_LOCATION WHERE PK_LOCATION = " . $loc_id);
                        if (!$loc_row->EOF) {
                            $selected_location_names[] = $loc_row->fields['LOCATION_CODE'];
                        }
                    }
                    ?>

                    <a class="topbar-link dropdown-toggle px-2" data-bs-toggle="dropdown" href="location" aria-haspopup="false" aria-expanded="false">
                        <!-- <img src="assets/images/logo.jpg" width="40" class="me-2 d-flex" alt="user-image" /> -->
                        <div class="d-flex align-items-center gap-1">
                            <span>
                                <h6 class="my-0 f14 lh-1 pro-username text-white location-display-name"><?= implode(', ', $selected_location_names) ?></h6>
                            </span>
                            <i class="ti ti-chevron-down align-middle"></i>
                        </div>
                    </a>

                    <!-- Multi-Select Dropdown -->
                    <div class="dropdown-menu dropdown-menu-end multi-select-dropdown" id="locationMultiSelect">
                        <div class="multi-select-header">Select Locations</div>
                        <div class="multi-select-options">

                            <?php
                            if (($_SESSION["PK_ROLES"] == 2 || $_SESSION["PK_ROLES"] == 11) || count($selected_location) > 1) { ?>
                                <?php
                                while (!$row->EOF) { ?>
                                    <div class="multi-select-option">
                                        <input type="checkbox" id="<?= $row->fields['PK_LOCATION'] ?>" value="<?= $row->fields['PK_LOCATION'] ?>" class="location-checkbox" <?= (!empty($_SESSION['DEFAULT_LOCATION_ID']) && in_array($row->fields['PK_LOCATION'], explode(',', $_SESSION['DEFAULT_LOCATION_ID']))) ? 'checked' : '' ?>>
                                        <label for="<?= $row->fields['PK_LOCATION'] ?>"><?= $row->fields['LOCATION_NAME'] ?> (<?= $row->fields['LOCATION_CODE'] ?>)</label>
                                    </div>
                                <?php $row->MoveNext();
                                } ?>
                            <?php } else {
                                $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_LOCATION IN (" . implode(',', $selected_location) . ")"); ?>
                                <h4 style="color: white;"><?= $row->fields['LOCATION_NAME'] ?></h4>
                            <?php } ?>
                        </div>
                        <div class="multi-select-footer">
                            <button class="clear-btn" onclick="clearLocations()">Clear All</button>
                            <button class="apply-btn" onclick="selectViewingLocation()">Apply</button>
                        </div>
                    </div>

                    <!-- <div class="dropdown-menu dropdown-menu-end">
                        <a href="#!" class="dropdown-item">
                            <span class="align-middle">Profile</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <span class="align-middle">Notifications</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <span class="align-middle">Account Settings</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <span class="align-middle">Support Center</span>
                        </a>
                        <a href="auth-lock-screen.html" class="dropdown-item">
                            <span class="align-middle">Lock Screen</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item fw-semibold">
                            <span class="align-middle">Log Out</span>
                        </a>
                    </div> -->
                </div>
            </div>
            <nav class="navbar navbar-expand-lg navbar-dark py-0">
                <div class="topbar-item d-none d-sm-flex" style="margin-left: -20px; margin-right: 20px;">
                    <a class="top-bar-icon" href="#">
                        <i class="fa fa-search" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                    <div class="navbar-nav">
                        <a class="nav-link <?= (('calendar.php' === $current_address || 'calendar_list_view.php' === $current_address) ? 'active' : '') ?>" href="calendar.php">Calendar</a>
                        <a class="nav-link <?= (('email.php' === $current_address) ? 'active' : '') ?>" href="../email/email.php?type=inbox">Messages</a>
                        <a class="nav-link <?= (('all_customers.php' === $current_address) ? 'active' : '') ?>" href="all_customers.php">Customers</a>
                        <a class="nav-link <?= (('payment_due_report.php' === $current_address) ? 'active' : '') ?>" href="payment_due_report.php">Billing</a>
                        <a class="nav-link <?= (('all_leads.php' === $current_address) ? 'active' : '') ?>" href="all_leads.php">Leads</a>
                        <a class="nav-link" href="#">Marketing</a>
                        <a class="nav-link <?= (('all_events.php' === $current_address) ? 'active' : '') ?>" href="all_events.php">Events</a>
                        <a class="nav-link <?= (('all_products.php' === $current_address) ? 'active' : '') ?>" href="all_products.php">E-Commerce</a>
                        <a class="nav-link <?= (('reports.php' === $current_address) ? 'active' : '') ?>" href="reports.php">Reports</a>
                    </div>
                </div>
            </nav>
        </div>

        <div class="d-flex align-items-center" style="gap: 2rem !important;">

            <div class="topbar-item d-none d-sm-flex">
                <a class="top-bar-icon" href="to_do_list.php">
                    <i class="fa fa-tasks" aria-hidden="true"></i>
                </a>
            </div>

            <div class="topbar-item d-none d-sm-flex">
                <a class="top-bar-icon" href="#">
                    <i class="fa fa-bell" aria-hidden="true"></i>
                </a>
            </div>

            <div class="topbar-item d-none d-sm-flex">
                <a class="top-bar-icon" href="setup.php">
                    <i class="fa fa-cog" aria-hidden="true"></i>
                </a>
            </div>


            <div id="user-dropdown-detailed" class="topbar-item nav-user">
                <div class="dropdown">
                    <a class="topbar-link dropdown-toggle px-2" data-bs-toggle="dropdown" href="profile-menu" aria-haspopup="false" aria-expanded="false">
                        <img src="assets/images/profile.png" width="32" class="rounded-circle me-2 d-flex" alt="user-image" />
                        <div class="d-flex align-items-center gap-1">
                            <h6 class="my-0 f14 lh-1 pro-username text-white fw-normal"><?= $_SESSION["FIRST_NAME"] . " " . $_SESSION["LAST_NAME"] ?></h6>
                            <i class="ti ti-chevron-down align-middle"></i>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="../admin/my_profile.php" class="dropdown-item">
                            <span class="align-middle">Profile</span>
                        </a>
                        <!-- <a href="javascript:void(0);" class="dropdown-item">
                            <span class="align-middle">Notifications</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <span class="align-middle">Account Settings</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <span class="align-middle">Support Center</span>
                        </a>
                        <a href="auth-lock-screen.html" class="dropdown-item">
                            <span class="align-middle">Lock Screen</span>
                        </a> -->
                        <a href="../logout.php" class="dropdown-item fw-semibold">
                            <span class="align-middle">Log Out</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    // Initialize multi-select dropdown
    document.addEventListener('DOMContentLoaded', function() {
        // Set initial checked locations
        const initialLocations = ['AMTO', 'AMWH'];
        initialLocations.forEach(loc => {
            const checkbox = document.querySelector(`.location-checkbox[value="${loc}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });

        // Prevent dropdown from closing when clicking inside the multi-select dropdown
        const multiSelectDropdown = document.getElementById('locationMultiSelect');
        if (multiSelectDropdown) {
            multiSelectDropdown.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        }
    });

    function applyLocations() {
        const checkedCheckboxes = document.querySelectorAll('.location-checkbox:checked');
        const selectedValues = Array.from(checkedCheckboxes).map(cb => cb.value);

        // Update the display text
        const displayText = selectedValues.length > 0 ? selectedValues.join(', ') : 'Select locations';
        document.querySelector('.selected-locations').textContent = displayText;

        // You can add additional logic here to save the selection or filter data
        console.log('Selected locations:', selectedValues);

        // Close the dropdown
        const dropdownButton = document.querySelector('[data-bs-toggle="dropdown"][href="location"]');
        if (dropdownButton) {
            const bsDropdown = new bootstrap.Dropdown(dropdownButton);
            bsDropdown.hide();
        }
    }

    function clearLocations() {
        // Uncheck all checkboxes
        document.querySelectorAll('.location-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });

        // Update display text
        document.querySelector('.selected-locations').textContent = 'Select locations';
    }

    // Optional: Add keyboard support (Enter to apply, Escape to close)
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' && document.querySelector('#locationMultiSelect').offsetParent !== null) {
            applyLocations();
        }
    });


    function selectViewingLocation() {
        const checkedCheckboxes = document.querySelectorAll('.location-checkbox:checked');
        const DEFAULT_LOCATION_ID = Array.from(checkedCheckboxes).map(cb => cb.value);

        if (DEFAULT_LOCATION_ID.length === 0) {
            alert('Please select at least one location.');
            return;
        } else {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {
                    FUNCTION_NAME: 'selectDefaultLocation',
                    DEFAULT_LOCATION_ID: DEFAULT_LOCATION_ID
                },
                async: false,
                cache: false,
                success: function(result) {
                    //console.log(result);
                    window.location.reload();
                }
            });
        }
    }
</script>