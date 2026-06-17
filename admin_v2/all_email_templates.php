<!DOCTYPE html>
<html lang="en">
<?php
require_once('../global/config.php');
$title = "All Email Templates";

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Email Templates Page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php //require_once('../includes/header.php'); 
?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Setup Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/setup-styles.css" rel="stylesheet">
</head>

<body>

    <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">
        <div class="row g-4">
            <!-- Sidebar (same as users page) -->
            <div class="col-12 col-md-4 col-xl-2">
                <?php include 'layout/setup_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-12 col-md-8 col-xl-10">
                <div class="main-card">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
                        <div>
                            <h2 class="fw-semibold h4 mb-1"><?= htmlspecialchars($title) ?></h2>
                            <p class="text-muted small mb-0">Manage email templates and their configurations</p>
                        </div>
                        <button class="btn btn-success-custom rounded-pill d-flex align-items-center gap-2" onclick="window.location.href='email_template.php'">
                            <i class="bi bi-plus-lg"></i> Create New Email Template
                        </button>
                    </div>

                    <!-- Locations Table (modern design, similar to users + corporation style) -->
                    <div class="table-responsive">
                        <table id="myTable" class="table custom-table align-middle mb-4" data-page-length='50'>
                            <thead>
                                <tr>
                                    <th>Template Name</th>
                                    <th>Subject</th>
                                    <!-- <th>Content</th> -->
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $i = 1;
                                $row = $db_account->Execute("SELECT * FROM `DOA_EMAIL_TEMPLATE` WHERE PK_ACCOUNT_MASTER='$_SESSION[PK_ACCOUNT_MASTER]'");
                                while (!$row->EOF) { ?>
                                    <tr>
                                        <td onclick="editpage(<?= $row->fields['PK_EMAIL_TEMPLATE'] ?>);"><?= $row->fields['TEMPLATE_NAME'] ?></td>
                                        <td onclick="editpage(<?= $row->fields['PK_EMAIL_TEMPLATE'] ?>);"><?= $row->fields['SUBJECT'] ?></td>
                                        <!-- <td onclick="editpage(<?= $row->fields['PK_EMAIL_TEMPLATE'] ?>);"><?= $row->fields['CONTENT'] ?></td> -->
                                        <td>
                                            <a href="email_template.php?id=<?= $row->fields['PK_EMAIL_TEMPLATE'] ?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                            <?php if ($row->fields['ACTIVE'] == 1) { ?>
                                                <span class="active-box-green"></span>
                                            <?php } else { ?>
                                                <span class="active-box-red"></span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php $row->MoveNext();
                                    $i++;
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            $('#myTable').DataTable();
        });

        function ConfirmDelete(anchor) {
            var conf = confirm("Are you sure you want to delete?");
            if (conf)
                window.location = anchor.attr("href");
        }

        function editpage(id) {
            //alert(i);
            window.location.href = "email_template.php?id=" + id;
        }
    </script>
</body>

</html>