<?php
require_once('../global/config.php');
global $db;

if (empty($_GET['id']))
    $title = "Add Blog";
else
    $title = "Edit Blog";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1) {
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    $PK_BLOG = $_POST['PK_BLOG'];
    unset($_POST['PK_BLOG']);
    $BLOG_DATA = $_POST;
    if ($PK_BLOG == 0) {
        $FEATURED_IMAGE_URL = '';
        $file_name = '';

        if (isset($_FILES['FEATURED_IMAGE_URL']) && $_FILES['FEATURED_IMAGE_URL']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/blogs/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_name = basename($_FILES['FEATURED_IMAGE_URL']['name']);
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $file_name = time() . '.' . $file_ext;
            $upload_file = $upload_dir . $file_name;
            move_uploaded_file($_FILES['FEATURED_IMAGE_URL']['tmp_name'], $upload_file);
            $FEATURED_IMAGE_URL = $upload_file;
            $BLOG_DATA['FEATURED_IMAGE_URL'] = 'uploads/blogs/' . $file_name;
        }

        $BLOG_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $BLOG_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_BLOGS', $BLOG_DATA, 'insert');
    } else {
        $FEATURED_IMAGE_URL = '';
        $file_name = '';

        if (isset($_FILES['FEATURED_IMAGE_URL']) && $_FILES['FEATURED_IMAGE_URL']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/blogs/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_name = basename($_FILES['FEATURED_IMAGE_URL']['name']);
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $file_name = time() . '.' . $file_ext;
            $upload_file = $upload_dir . $file_name;
            move_uploaded_file($_FILES['FEATURED_IMAGE_URL']['tmp_name'], $upload_file);
            $FEATURED_IMAGE_URL = $upload_file;
            $BLOG_DATA['FEATURED_IMAGE_URL'] = 'uploads/blogs/' . $file_name;
        }

        $BLOG_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $BLOG_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_BLOGS', $BLOG_DATA, 'update', " PK_BLOG =  '$PK_BLOG'");
    }

    header("location:all_blog.php");
}

if (empty($_GET['id'])) {
    $TITLE = '';
    $SLUG = '';
    $EXCERPT = '';
    $CONTENT = '';
    $FEATURED_IMAGE_URL = '';
    $AUTHOR_NAME = '';
    $CATEGORY = '';
    $TAGS = '';
    $STATUS = '';
    $SEO_TITLE = '';
    $SEO_DESCRIPTION = '';
    $CANONICAL_URL = '';
    $PUBLISHED_AT = '';
    $COMMENTS_ENABLED = '';
    $EXTERNAL_SOURCE = '';
    $EXTERNAL_SOURCE_ID = '';
    $ACTIVE = '';
} else {
    $blog_data = $db->Execute("SELECT * FROM `DOA_BLOGS` WHERE PK_BLOG = '$_GET[id]'");
    if ($blog_data->RecordCount() == 0) {
        header("location:all_blog.php");
        exit;
    }
    $TITLE = $blog_data->fields['TITLE'];
    $SLUG = $blog_data->fields['SLUG'];
    $EXCERPT = $blog_data->fields['EXCERPT'];
    $CONTENT = $blog_data->fields['CONTENT'];
    $FEATURED_IMAGE_URL = $blog_data->fields['FEATURED_IMAGE_URL'];
    $AUTHOR_NAME = $blog_data->fields['AUTHOR_NAME'];
    $CATEGORY = $blog_data->fields['CATEGORY'];
    $TAGS = $blog_data->fields['TAGS'];
    $STATUS = $blog_data->fields['STATUS'];
    $SEO_TITLE = $blog_data->fields['SEO_TITLE'];
    $SEO_DESCRIPTION = $blog_data->fields['SEO_DESCRIPTION'];
    $CANONICAL_URL = $blog_data->fields['CANONICAL_URL'];
    $PUBLISHED_AT = $blog_data->fields['PUBLISHED_AT'];
    $COMMENTS_ENABLED = $blog_data->fields['COMMENTS_ENABLED'];
    $EXTERNAL_SOURCE = $blog_data->fields['EXTERNAL_SOURCE'];
    $EXTERNAL_SOURCE_ID = $blog_data->fields['EXTERNAL_SOURCE_ID'];
    $ACTIVE = $blog_data->fields['ACTIVE'];
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
                                <li class="breadcrumb-item"><a href="all_blog.php">All Blogs</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>

                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">

                                <form class="form-material form-horizontal m-t-30" action="" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="PK_BLOG" value="<?= empty($_GET['id']) ? '0' : $_GET['id'] ?>">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Title<span class="text-danger">*</span></label>
                                                <div class="col-md-12">
                                                    <input type="text" id="TITLE" name="TITLE" class="form-control" placeholder="Enter Title" required value="<?php echo $TITLE ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Slug<span class="text-danger">*</span></label>
                                                <div class="col-md-12">
                                                    <input type="text" id="SLUG" name="SLUG" class="form-control" placeholder="Enter Slug" required value="<?php echo $SLUG ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Excerpt</label>
                                                <div class="col-md-12">
                                                    <input type="text" id="EXCERPT" name="EXCERPT" class="form-control" placeholder="Enter Excerpt" value="<?php echo $EXCERPT ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Category</label>
                                                <div class="col-md-12">
                                                    <input type="text" id="CATEGORY" name="CATEGORY" class="form-control" placeholder="Enter Category" value="<?php echo $CATEGORY ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Content</label>
                                                <div class="col-md-12">
                                                    <textarea id="ck_editor" name="CONTENT" class="form-control" placeholder="Enter Content" rows="50"><?php echo $CONTENT ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Featured Image URL</label>
                                                <div class="col-md-12">
                                                    <input type="file" id="FEATURED_IMAGE_URL" name="FEATURED_IMAGE_URL" class="form-control">
                                                    <img src="<?php echo '../' . $FEATURED_IMAGE_URL ?>" alt="Featured Image" style="max-width: 300px; max-height: auto; margin-top: 10px;">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Author Name</label>
                                                <div class="col-md-12">
                                                    <input type="text" id="AUTHOR_NAME" name="AUTHOR_NAME" class="form-control" placeholder="Enter Author Name" value="<?php echo $AUTHOR_NAME ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Tags</label>
                                                <div class="col-md-12">
                                                    <input type="text" id="TAGS" name="TAGS" class="form-control" placeholder="Enter Tags" value="<?php echo $TAGS ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Status</label>
                                                <div class="col-md-12">
                                                    <select class="form-control" id="STATUS" name="STATUS">
                                                        <option value="1" <?php if ($STATUS == 1) echo 'selected'; ?>>Draft</option>
                                                        <option value="2" <?php if ($STATUS == 2) echo 'selected'; ?>>Published</option>
                                                        <option value="3" <?php if ($STATUS == 3) echo 'selected'; ?>>Archived</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">SEO Title</label>
                                                <div class="col-md-12">
                                                    <input type="text" id="SEO_TITLE" name="SEO_TITLE" class="form-control" placeholder="Enter SEO Title" value="<?php echo $SEO_TITLE ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">SEO Description</label>
                                                <div class="col-md-12">
                                                    <input type="text" id="SEO_DESCRIPTION" name="SEO_DESCRIPTION" class="form-control" placeholder="Enter SEO Description" value="<?php echo $SEO_DESCRIPTION ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Canonical URL</label>
                                                <div class="col-md-12">
                                                    <input type="text" id="CANONICAL_URL" name="CANONICAL_URL" class="form-control" placeholder="Enter Canonical URL" value="<?php echo $CANONICAL_URL ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Comments Enabled</label>
                                                <div class="col-md-12">
                                                    <select class="form-control" id="COMMENTS_ENABLED" name="COMMENTS_ENABLED">
                                                        <option value="1" <?php if ($COMMENTS_ENABLED == 1) echo 'selected'; ?>>Yes</option>
                                                        <option value="0" <?php if ($COMMENTS_ENABLED == 0) echo 'selected'; ?>>No</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">External Source</label>
                                                <div class="col-md-12">
                                                    <select class="form-control" id="EXTERNAL_SOURCE" name="EXTERNAL_SOURCE">
                                                        <option value="1" <?php if ($EXTERNAL_SOURCE == 1) echo 'selected'; ?>>Yes</option>
                                                        <option value="0" <?php if ($EXTERNAL_SOURCE == 0) echo 'selected'; ?>>No</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">External Source ID</label>
                                                <div class="col-md-12">
                                                    <input type="text" id="EXTERNAL_SOURCE_ID" name="EXTERNAL_SOURCE_ID" class="form-control" placeholder="Enter External Source ID" value="<?php echo $EXTERNAL_SOURCE_ID ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($_GET['id'])) { ?>
                                        <div class="row" style="margin-bottom: 15px; margin-top: 10px">
                                            <div class="col-6">
                                                <div class="col-md-2">
                                                    <label>Active</label>
                                                </div>
                                                <div class="col-md-4">
                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if ($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if ($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                </div>
                                            </div>
                                        </div>
                                    <? } ?>

                                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                    <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_blogs.php'">Cancel</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
    <script src="../assets/ckeditor/ckeditor.js"></script>
    <script>
        $('.multi_sumo_select').SumoSelect({
            placeholder: 'Select Permission',
            selectAll: true
        });
        const editor = CKEDITOR.replace('ck_editor', {
            versionCheck: false,
            height: '500px',
        });
    </script>
</body>

</html>