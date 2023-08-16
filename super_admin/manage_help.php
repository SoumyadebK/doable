<?error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
require_once("../global/config.php");
$title='All Helps';
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
        <div class="container-fluid body_content">
                 <div class="row page-titles">
                    <div class="col-md-7 align-self-center">
                        <h4 class="text-themecolor">Knowledge Base </h4>
                    </div>
					<!-- <div class="col-md-3 align-self-center text-right">
                        <input type="text" class="form-control" id="SEARCH" name="SEARCH" placeholder="&#xF002; Search"  style="font-family: FontAwesome" onkeypress="search(event)">
					</div> -->
                    <div class="col-md-5 align-self-right text-right">
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
											$res_type = $db->Execute("
												SELECT PK_HELP,NAME_ENG,HELP_CATEGORY, HELP_SUB_CATEGORY, DOA_Z_HELP.ACTIVE, DOA_Z_HELP.DISPLAY_ORDER FROM DOA_Z_HELP 
												LEFT JOIN DOA_M_HELP_CATEGORY ON DOA_M_HELP_CATEGORY.PK_HELP_CATEGORY = DOA_Z_HELP.PK_HELP_CATEGORY 
												LEFT JOIN DOA_M_HELP_SUB_CATEGORY ON DOA_M_HELP_SUB_CATEGORY.PK_HELP_SUB_CATEGORY = DOA_Z_HELP.PK_HELP_SUB_CATEGORY 
												 ORDER BY DISPLAY_ORDER
												");
										?>
										<table id="myTable" class="table table-striped border">
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
