<?error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
require_once("../global/config.php");
$title='All Helps';

$status_check = empty($_GET['status'])?'active':$_GET['status'];

if ($status_check == 'active'){
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' ){
	header("location:../index.php");
	exit;
}
if(isset($_GET['act'])){
if($_GET['act'] == 'del')	{
	$db->Execute("DELETE FROM DOA_Z_HELP WHERE PK_HELP = '$_GET[id]' ");
	header("location:manage_help");
}
} 


//$ret_res = mysql_query($res)or die(mysql_error());

//echo "<pre>";print_r($ret_res); EXIT;

$results_per_page = 100;

if (isset($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = " AND (DOA_Z_HELP.NAME_ENG LIKE '%".$search_text."%')";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db->Execute("SELECT count(DOA_Z_HELP.PK_HELP) AS TOTAL_RECORDS FROM DOA_Z_HELP");
$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}

$page_first_result = ($page-1) * $results_per_page;
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
	<?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <?php require_once('../includes/setup_menu_super_admin.php') ?>
        <div class="container-fluid body_content m-0">
                 <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor">Knowledge Base </h4>
                    </div>
                     <div class="col-md-3 align-self-center text-end">
                         <form class="form-material form-horizontal" action="" method="get">
                             <input type="hidden" name="status" value="<?=$status_check?>" >
                             <div class="input-group">
                                 <input class="form-control" type="text" name="search_text" placeholder="Search.." value="<?=$search_text?>">
                                 <button class="btn btn-info waves-effect waves-light m-r-10 text-white input-group-btn m-b-1" type="submit"><i class="fa fa-search"></i></button>
                             </div>
                         </form>
                     </div>
					<!-- <div class="col-md-3 align-self-center text-right">
                        <input type="text" class="form-control" id="SEARCH" name="SEARCH" placeholder="&#xF002; Search"  style="font-family: FontAwesome" onkeypress="search(event)">
					</div> -->
                    <div class="col-md-4 align-self-right text-right">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item active"><?=$title?></li>
                            </ol>
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='help.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="row">
									<div class="col-md-12">
										<?php
											$res_type = $db->Execute("SELECT PK_HELP,NAME_ENG,HELP_CATEGORY, HELP_SUB_CATEGORY, DOA_Z_HELP.ACTIVE, DOA_Z_HELP.DISPLAY_ORDER FROM DOA_Z_HELP LEFT JOIN DOA_M_HELP_CATEGORY ON DOA_M_HELP_CATEGORY.PK_HELP_CATEGORY = DOA_Z_HELP.PK_HELP_CATEGORY LEFT JOIN DOA_M_HELP_SUB_CATEGORY ON DOA_M_HELP_SUB_CATEGORY.PK_HELP_SUB_CATEGORY = DOA_Z_HELP.PK_HELP_SUB_CATEGORY WHERE DOA_Z_HELP.ACTIVE=1 ".$search." ORDER BY DISPLAY_ORDER LIMIT " . $page_first_result . ',' . $results_per_page);
										?>
										<table class="table table-striped border">
			                    			<thead>
			                    				<tr>
													<th>ID</th>
													<th>Category</th>
													<th>Subcategory</th>
													<th>Display Order</th>
													<th>Title</th>
													<th>Options</th>
												</tr>
			                    			</thead>
			                    			<tbody>
			                    				<?php $sl = 1; ?> 	
												<?php while (!$res_type->EOF) : ?>					
													    <tr>
													      <td onclick="editpage(<?=$res_type->fields['PK_HELP'];?>);"><?php echo $sl++; ?></td> 	
									                      <td onclick="editpage(<?=$res_type->fields['PK_HELP'];?>);"><?php echo $res_type->fields['HELP_CATEGORY']; ?></td>
									                      <td onclick="editpage(<?=$res_type->fields['PK_HELP'];?>);"><?php echo $res_type->fields['HELP_SUB_CATEGORY']; ?></td>
									                      <td onclick="editpage(<?=$res_type->fields['PK_HELP'];?>);"><?php echo $res_type->fields['DISPLAY_ORDER']; ?></td>
									                      <td onclick="editpage(<?=$res_type->fields['PK_HELP'];?>);"><?php echo $res_type->fields['NAME_ENG']; ?></td>
			                                              <td>
			                                                 <a href="help.php?id=<?=$res_type->fields['PK_HELP']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			                                                  <a href="" onclick='javascript:delete_row(<?=$res_type->fields['PK_HELP']?>);return false;'><img src="../assets/images/delete.png" title="Delete" style="padding-top:3px"></a>
			                                                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		                                                  	<?php if($res_type->fields['ACTIVE']==1){ ?>
	                                                            <span class="active-box-green"></span>
	                                                        <?php } else{ ?>
	                                                            <span class="active-box-red"></span>
	                                                        <?php } ?>
			                                              </td>
									                    </tr>
												<?php $res_type->MoveNext(); endwhile; ?>
			                    			</tbody>
			                    		</table>
                                        <div class="center">
                                            <div class="pagination outer">
                                                <ul>
                                                    <?php if ($page > 1) { ?>
                                                        <li><a href="manage_help.php?status=<?=$status_check?>&page=1">&laquo;</a></li>
                                                        <li><a href="manage_help.php?status=<?=$status_check?>&page=<?=($page-1)?>">&lsaquo;</a></li>
                                                    <?php }
                                                    for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                                                        if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                                                            echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="manage_help.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                        } elseif ($page_count == ($number_of_page-1)){
                                                            echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                        } else {
                                                            echo '<li><a class="hidden" href="manage_help.php?status=' . $status_check . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                        }
                                                    }
                                                    if ($page < $number_of_page) { ?>
                                                        <li><a href="manage_help.php?status=<?=$status_check?>&page=<?=($page+1)?>">&rsaquo;</a></li>
                                                        <li><a href="manage_help.php?status=<?=$status_check?>&page=<?=$number_of_page?>">&raquo;</a></li>
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

        <?php require_once('../includes/footer.php');?>
		<div class="modal" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title" id="exampleModalLabel1">Delete Confirmation</h4>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body">
						<div class="form-group">
							Are you sure want to Delete this Record?
							<input type="hidden" id="DELETE_ID" value="0" />
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" onclick="conf_delete(1)" class="btn waves-effect waves-light btn-info">Yes</button>
						<button type="button" class="btn waves-effect waves-light btn-dark" onclick="conf_delete(0)" >No</button>
					</div>
				</div>
			</div>
		</div>
    </div>
	<? //;require_once("js.php"); ?>

	<!-- <script src="../backend_assets/dist/js/jquery-migrate-1.0.0.js"></script>
	<script type="text/javascript" src="../backend_assets/dist/js/jquery.easyui.min.js"></script>
	<script src="../backend_assets/dist/js/jquery-ui.js"></script> -->

	<script>
        $(function () {
            $('#myTable').DataTable();
        });
    </script>

	<script type="text/javascript">
	
	function delete_row(id){
		jQuery(document).ready(function($) {
			$("#deleteModal").show()
			$("#DELETE_ID").val(id)
		});
	}
	function conf_delete(val,id){
		if(val == 1)
			window.location.href = 'manage_help?act=del&id='+$("#DELETE_ID").val();
		else
			$("#deleteModal").hide();
	}
	function editpage(id){
        window.location.href = "help.php?id="+id;
    }
	</script>

</body>

</html>
