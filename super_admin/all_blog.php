<?php
require_once('../global/config.php');
$title = "All Blogs";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1) {
    header("location:../login.php");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id = $_GET['id'];
    if (empty($id)) {
        header("location:../login.php");
        exit;
    }
    $db->Execute("DELETE FROM `DOA_BLOGS` WHERE `PK_BLOG` = '$id'");
    header("location:all_blog.php");
    exit;
}

function getFirstTagContent($html)
{
    // Method 1: Using regex (faster for simple cases)
    if (preg_match('/<([^>\s]+)[^>]*>(.*?)<\/\1>/s', $html, $matches)) {
        return $matches[2];
    }

    return null;
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
            <?php require_once('../includes/setup_menu_super_admin.php') ?>
            <div class="container-fluid body_content m-0">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='add_blog.php'"><i class="fa fa-plus-circle"></i> Add New Blog</button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="myTable" class="table table-striped border">
                                        <thead>
                                            <tr>
                                                <th width="20%">Title</th>
                                                <th width="15%">Slug</th>
                                                <th width="25%">Content</th>
                                                <th width="10%">Status</th>
                                                <th width="10%">Comments</th>
                                                <th width="10%">Created On</th>
                                                <th width="10%">Actions</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                            $i = 1;
                                            $row = $db->Execute("SELECT * FROM `DOA_BLOGS` ORDER BY `CREATED_ON` DESC");
                                            while (!$row->EOF) { ?>
                                                <tr>
                                                    <td onclick="editpage(<?= $row->fields['PK_BLOG'] ?>);"><?= $row->fields['TITLE'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_BLOG'] ?>);"><?= $row->fields['SLUG'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_BLOG'] ?>);"><b style="font-weight: bold; font-size: 16px;"><?= getFirstTagContent($row->fields['CONTENT']) ?></b></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_BLOG'] ?>);">
                                                        <?php if ($row->fields['STATUS'] == 1) { ?>
                                                            <span class="badge bg-success">Draft</span>
                                                        <?php } elseif ($row->fields['STATUS'] == 2) { ?>
                                                            <span class="badge bg-warning">Published</span>
                                                        <?php } elseif ($row->fields['STATUS'] == 3) { ?>
                                                            <span class="badge bg-danger">Archived</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td><a href="all_comment.php?id=<?= $row->fields['PK_BLOG'] ?>" title="Comments">Comments</a></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_BLOG'] ?>);"><?= $row->fields['CREATED_ON'] ?></td>
                                                    <td>
                                                        <a href="add_blog.php?id=<?= $row->fields['PK_BLOG'] ?>" title="Edit"><i class="ti-pencil" style="font-size: 20px;"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <a href="all_blog.php?id=<?= $row->fields['PK_BLOG'] ?>&action=delete" title="Delete" onclick="return confirm('Are you sure you want to delete this blog?');"><i class="ti-trash" style="font-size: 20px;"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <?php if ($row->fields['ACTIVE'] == 1) { ?>
                                                            <span class="active-box-green" style="margin-top:0px;"></span>
                                                        <?php } else { ?>
                                                            <span class="active-box-red" style="margin-top:0px;"></span>
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
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
    <script>
        $(function() {
            $('#myTable').DataTable({
                "order": [
                    [4, 'desc']
                ]
            });
        });

        function editpage(id) {
            window.location.href = "add_blog.php?id=" + id;
        }
    </script>
</body>

</html>