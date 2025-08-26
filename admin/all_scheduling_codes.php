<?php
require_once('../global/config.php');
$title = "All Scheduling Codes";

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$status_check = empty($_GET['status']) ? 'active' : $_GET['status'];

if ($status_check == 'active') {
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$FUNCTION_NAME = isset($_POST['FUNCTION_NAME']) ? $_POST['FUNCTION_NAME'] : '';
if (!empty($_POST) && $FUNCTION_NAME == 'saveSortOrder') {
    $db_account->Execute("UPDATE DOA_SCHEDULING_CODE SET SORT_ORDER=" . $_POST['ORDER_NUMBER'] . " WHERE PK_SCHEDULING_CODE=" . $_POST['PK_SCHEDULING_CODE']);
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Scheduling Codes page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}
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
                        <?php if ($status_check == 'inactive') { ?>
                            <h4 class="text-themecolor">Not Active Scheduling Codes</h4>
                        <?php } elseif ($status_check == 'active') { ?>
                            <h4 class="text-themecolor">Active Scheduling Codes</h4>
                        <?php } ?>
                    </div>
                    <?php if ($status_check == 'inactive') { ?>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_scheduling_codes.php?status=active'"><i class="fa fa-user"></i> Show Active</button>
                        </div>
                    <?php } elseif ($status_check == 'active') { ?>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_scheduling_codes.php?status=inactive'"><i class="fa fa-user-times"></i> Show Not Active</button>
                        </div>
                    <?php } ?>
                    <div class="col-md-4 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='add_scheduling_codes.php'"><i class="fa fa-plus-circle"></i> Create New</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row" style="text-align: center;">
                                    <h5 style="font-weight: bold;"><?= $header_text ?></h5>
                                </div>
                                <div class="table-responsive">
                                    <table id="myTable" class="table table-striped border" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                <th>Scheduling code</th>
                                                <th>Scheduling Name</th>
                                                <th>Duration</th>
                                                <th>Unit</th>
                                                <th>Color</th>
                                                <th>Sort Order</th>
                                                <th>To-Dos</th>
                                                <th>Action</th>
                                                <th></th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                            $i = 1;
                                            $row = $db_account->Execute("SELECT DISTINCT DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE, DOA_SCHEDULING_CODE.SCHEDULING_CODE, DOA_SCHEDULING_CODE.SCHEDULING_NAME, DOA_SCHEDULING_CODE.DURATION, DOA_SCHEDULING_CODE.UNIT, DOA_SCHEDULING_CODE.COLOR_CODE, DOA_SCHEDULING_CODE.SORT_ORDER, DOA_SCHEDULING_CODE.TO_DOS, DOA_SCHEDULING_CODE.ACTIVE FROM `DOA_SCHEDULING_CODE` LEFT JOIN DOA_SCHEDULING_SERVICE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE=DOA_SCHEDULING_SERVICE.PK_SCHEDULING_CODE WHERE PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_SCHEDULING_CODE.ACTIVE =" . $status . " AND DOA_SCHEDULING_CODE.PK_ACCOUNT_MASTER=" . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY CASE WHEN DOA_SCHEDULING_CODE.SORT_ORDER IS NULL THEN 1 ELSE 0 END, DOA_SCHEDULING_CODE.SORT_ORDER");
                                            while (!$row->EOF) { ?>
                                                <tr data-id="<?= $row->fields['PK_SCHEDULING_CODE'] ?>">
                                                    <td class="code"><?= $row->fields['SCHEDULING_CODE'] ?></td>
                                                    <td class="name"><?= $row->fields['SCHEDULING_NAME'] ?></td>
                                                    <td class="duration"><?= $row->fields['DURATION'] ?></td>
                                                    <td class="unit"><?= $row->fields['UNIT'] ?></td>
                                                    <td class="color" data-value="<?= $row->fields['COLOR_CODE'] ?>"><span style="display: block; width: 44px; height: 22px; background-color: <?= $row->fields['COLOR_CODE'] ?>"></span></td>
                                                    <td class="order"><?= $row->fields['SORT_ORDER'] ?></td>
                                                    <td class="todos" data-value="<?= $row->fields['TO_DOS'] ?>" style="text-align: center">
                                                        <?php if ($row->fields['TO_DOS'] == 1) { ?>
                                                            <input type="checkbox" class="active-checkbox" checked disabled>
                                                        <?php } else { ?>
                                                            <input type="checkbox" class="active-checkbox" disabled>
                                                        <?php } ?>
                                                    </td>
                                                    <td class="is_active" data-value="<?= $row->fields['ACTIVE'] ?>">
                                                        <?php if ($row->fields['ACTIVE'] == 1) { ?>
                                                            <span class="active-box-green"></span>
                                                        <?php } else { ?>
                                                            <span class="active-box-red"></span>
                                                        <?php } ?>
                                                        <!--<a href="add_scheduling_codes.php?id=<?php /*=$row->fields['PK_SCHEDULING_CODE']*/ ?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-->
                                                    </td>
                                                    <td>
                                                        <button class="editBtn btn btn-info waves-effect waves-light m-r-10 text-white myBtn">Edit</button>
                                                        <button class="saveBtn btn btn-info waves-effect waves-light m-r-10 text-white myBtn" style="display: none">Save</button>
                                                    </td>
                                                </tr>
                                            <?php $row->MoveNext();
                                                $i++;
                                            } ?>
                                        </tbody>
                                    </table>

                                    <!--<div class="modal fade" id="sort_order_modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form id="sort_order_form" role="form" action="all_scheduling_codes.php" method="post">
                                            <div class="modal-content" style="width: 50%; margin: 15% auto;">
                                                <div class="modal-header">
                                                    <h4><b>Sort Order</b></h4>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="FUNCTION_NAME" value="saveSortOrder">
                                                    <input type="hidden" name="PK_SCHEDULING_CODE" id="PK_SCHEDULING_CODE" class="PK_SCHEDULING_CODE">
                                                    <div class="p-20">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Set Order</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="ORDER_NUMBER" id="ORDER_NUMBER" class="form-control">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" id="card-button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>-->

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
            $('#myTable').DataTable({
                "order": false
            });
        });

        function ConfirmDelete(anchor) {
            var conf = confirm("Are you sure you want to delete?");
            if (conf)
                window.location = anchor.attr("href");
        }

        function editpage(id) {
            //alert(i);
            window.location.href = "add_scheduling_codes.php?id=" + id;
        }

        /*function editSortOrder(PK_SCHEDULING_CODE, SORT_ORDER) {
            $('#PK_SCHEDULING_CODE').val(PK_SCHEDULING_CODE);
            $('#ORDER_NUMBER').val(SORT_ORDER);
            $('#sort_order_modal').modal('show');
        }*/

        // Get all the edit buttons and save buttons in the table
        const editButtons = document.querySelectorAll('.editBtn');
        const saveButtons = document.querySelectorAll('.saveBtn');

        // Loop through each edit button and add an event listener
        editButtons.forEach((editButton, index) => {
            editButton.addEventListener('click', function() {
                // Find the row containing the clicked button
                const row = editButton.closest('tr');
                const codeCell = row.querySelector('.code');
                const nameCell = row.querySelector('.name');
                const durationCell = row.querySelector('.duration');
                const unitCell = row.querySelector('.unit');
                const colorCell = row.querySelector('.color');
                const orderCell = row.querySelector('.order');
                const todosCell = row.querySelector('.todos');
                const activeCell = row.querySelector('.is_active');

                // Replace the cell content with input fields containing the current text
                codeCell.innerHTML = `<input type="text" value="${codeCell.textContent}" class="edit-code">`;
                nameCell.innerHTML = `<input type="text" value="${nameCell.textContent}" class="edit-name">`;
                durationCell.innerHTML = `<input type="text" value="${durationCell.textContent}" class="edit-duration">`;
                unitCell.innerHTML = `<input type="text" value="${unitCell.textContent}" class="edit-unit">`;
                colorCell.innerHTML = `<input type="color" value="${colorCell.getAttribute('data-value')}" class="edit-color">`;
                orderCell.innerHTML = `<input type="text" value="${orderCell.textContent}" class="edit-order">`;
                todosCell.innerHTML = `<input type="checkbox" class="edit-todos" ${(todosCell.getAttribute('data-value') == 1) ? 'checked' : ''}>`;
                activeCell.innerHTML = `<input type="checkbox" class="edit-active" ${(activeCell.getAttribute('data-value') == 1) ? 'checked' : ''}>`;

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

                const codeCell = row.querySelector('.code');
                const nameCell = row.querySelector('.name');
                const durationCell = row.querySelector('.duration');
                const unitCell = row.querySelector('.unit');
                const colorCell = row.querySelector('.color');
                const orderCell = row.querySelector('.order');
                const todosCell = row.querySelector('.todos');
                const activeCell = row.querySelector('.is_active');

                const editCode = row.querySelector('.edit-code');
                const editName = row.querySelector('.edit-name');
                const editDuration = row.querySelector('.edit-duration');
                const editUnit = row.querySelector('.edit-unit');
                const editColour = row.querySelector('.edit-color');
                const editOrder = row.querySelector('.edit-order');
                const editTodos = row.querySelector('.edit-todos');
                const editActive = row.querySelector('.edit-active');

                // Get the updated values from the input fields
                const updatedCode = editCode.value;
                const updatedName = editName.value;
                const updatedDuration = editDuration.value;
                const updatedUnit = editUnit.value;
                const updatedColour = editColour.value;
                const updatedOrder = editOrder.value;
                const updatedTodos = editTodos.checked;
                const updatedActive = editActive.checked;


                // Assuming you have an ID field (for example, as a data attribute)
                const id = row.getAttribute('data-id');

                // Prepare the data to be sent
                const data = {
                    id: id,
                    code: updatedCode,
                    name: updatedName,
                    duration: updatedDuration,
                    unit: updatedUnit,
                    color: updatedColour,
                    order: updatedOrder,
                    todos: updatedTodos,
                    active: updatedActive
                };

                // Send the updated data to the backend using Fetch API
                fetch('includes/save_scheduling_code.php', {
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
                            codeCell.textContent = updatedCode;
                            nameCell.textContent = updatedName;
                            durationCell.textContent = updatedDuration;
                            unitCell.textContent = updatedUnit;
                            colorCell.innerHTML = `<span style="display: block; width: 44px; height: 22px; background-color: ${updatedColour}"></span>`;
                            orderCell.textContent = updatedOrder;
                            todosCell.innerHTML = `<input type="checkbox" class="active-checkbox" ${(updatedTodos == 1) ? 'checked' : ''} disabled>`;
                            activeCell.innerHTML = `${(updatedActive == 1) ? '<span class="active-box-green"></span>' : '<span class="active-box-red"></span>'}`

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
</body>

</html>