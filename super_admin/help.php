<?error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
require_once("../global/config.php");
if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' ){
	header("location:../index.php");
	exit;
}
//$_GET['act'] = '';
if($_GET['act'] == 'delImg')	{
	$res = $db->Execute("SELECT FILE_NAME FROM DOA_Z_HELP_FILES WHERE PK_HELP = '$_GET[id]' ");
	unlink($res->fields['FILE_NAME']);
	$db->Execute("DELETE FROM DOA_Z_HELP_FILES WHERE PK_HELP = '$_GET[id]' AND PK_HELP_FILES = '$_GET[iid]' ");

	header("location:help.php?id=".$_GET['id']);
}

if(!empty($_POST)){
	// echo "<pre>";print_r($_POST);exit;
	$HELP = $_POST;

	if($_GET['id'] == ''){
		$HELP['CREATED_BY']  = $_SESSION['PK_USER'];
		$HELP['CREATED_ON']  = date("Y-m-d H:i");

		if(empty($_POST['DISPLAY_ORDER'])) unset($HELP['DISPLAY_ORDER']);

		db_perform('DOA_Z_HELP', $HELP, 'insert');
		$PK_HELP = $db->insert_ID();
	} else {
		$HELP['EDITED_BY'] = $_SESSION['PK_USER'];
		$HELP['EDITED_ON'] = date("Y-m-d H:i");
		db_perform('DOA_Z_HELP', $HELP, 'update'," PK_HELP = '$_GET[id]'");
		$PK_HELP = $_GET['id'];
	}

	$i = 0;
	$file_dir_1 = '../uploads/help_image/';
	for($k = 0 ; $k < count($_FILES['ATTACHMENT']['name']) ; $k++){

		$extn 			= explode(".",$_FILES['ATTACHMENT']['name'][$i]);
		$iindex			= count($extn) - 1;
		$rand_string 	= time()."_".rand(10000,99999);
		$file11			= $rand_string.".".$extn[$iindex];
		$extension   	= strtolower($extn[$iindex]);

		if($extension != "php" && $extension != "js" && $extension != "html" && $extension != "htm"  ){
			$newfile1    = $file_dir_1.$file11;

			move_uploaded_file($_FILES['ATTACHMENT']['tmp_name'][$i], $newfile1);

			$FILE_TYPE = '';
			if($extension == 'png' || $extension == 'jpg' || $extension == 'jepg')
				$FILE_TYPE = 1;
			else if($extension == 'pdf')
				$FILE_TYPE = 2;

			$HELP_FILES['FILE_LOCATION'] 	= $newfile1;
			$HELP_FILES['FILE_NAME'] 		= $_FILES['ATTACHMENT']['name'][$i];
			$HELP_FILES['PK_HELP'] 			= $PK_HELP;
			$HELP_FILES['FILE_TYPE'] 		= $FILE_TYPE;
			$HELP_FILES['CREATED_BY']  		= $_SESSION['PK_USER'];
			$HELP_FILES['CREATED_ON']  		= date("Y-m-d H:i");

			db_perform('DOA_Z_HELP_FILES', $HELP_FILES, 'insert');
		}

		$i++;
	}


	header("location:manage_help.php");
}

//$_GET['id'] ='';
if($_GET['id'] == ''){
	$PK_HELP_CATEGORY 		= '';
	$PK_HELP_SUB_CATEGORY 	= '';

	$NAME_ENG 	 	  = '';
	$NAME_SPA 	 	  = '';
	$TOOL_CONTENT_ENG = '';
	$TOOL_CONTENT_SPA = '';
	$CONTENT_ENG 	  = '';
	$CONTENT_SPA 	  = '';
	$IMAGE 		 	  = '';
	$ACTIVE	 	 	  = '';

	$URL	 	 	  = '';
	$DISPLAY_ORDER	  = '';

} else {
	$res = $db->Execute("SELECT * FROM DOA_Z_HELP WHERE PK_HELP = '$_GET[id]' ");
	if($res->RecordCount() == 0){
		header("location:manage_help.php");
		exit;
	}

	$PK_HELP_CATEGORY 		= $res->fields['PK_HELP_CATEGORY'];
	$PK_HELP_SUB_CATEGORY 	= $res->fields['PK_HELP_SUB_CATEGORY'];
	$NAME_ENG 	 	  		= $res->fields['NAME_ENG'];
	$NAME_SPA 	 	  		= $res->fields['NAME_SPA'];
	$TOOL_CONTENT_ENG 		= $res->fields['TOOL_CONTENT_ENG'];
	$TOOL_CONTENT_SPA	 	= $res->fields['TOOL_CONTENT_SPA'];
	$CONTENT_ENG 	  		= $res->fields['CONTENT_ENG'];
	$CONTENT_SPA	  		= $res->fields['CONTENT_SPA'];
	$IMAGE 		 	  		= $res->fields['IMAGE'];
	$ACTIVE  	 	  		= $res->fields['ACTIVE'];
	$URL  	 	  	  		= $res->fields['URL'];
	$DISPLAY_ORDER    		= $res->fields['DISPLAY_ORDER'];
}
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
        <div class="container-fluid">
                 <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><? if($_GET['id'] == '') echo "Add"; else echo "Edit"; ?> Knowledge Base </h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form class="floating-labels m-t-40" method="post" name="form1" id="form1" enctype="multipart/form-data" >
									<div class="row">
                                        <div class="col-md-6">
											<div class="form-group m-b-40">
												<label for="PK_HELP_CATEGORY">Category</label>
												<span class="bar"></span>
												<select class="form-control" id="PK_HELP_CATEGORY" name="PK_HELP_CATEGORY" onchange="get_sub_category(this.value)" required="required">
													<option value="" >Select Category</option>
													<? $res_dd = $db->Execute("select PK_HELP_CATEGORY,HELP_CATEGORY FROM DOA_M_HELP_CATEGORY WHERE ACTIVE = 1 ORDER BY HELP_CATEGORY ASC");
													while (!$res_dd->EOF) { ?>
														<option value="<?=$res_dd->fields['PK_HELP_CATEGORY']?>" <? if($res_dd->fields['PK_HELP_CATEGORY'] == $PK_HELP_CATEGORY ) echo "selected"; ?> ><?=$res_dd->fields['HELP_CATEGORY'] ?></option>
													<?	$res_dd->MoveNext();
													} ?>
												</select>
											</div>
										</div>

                                        <div class="col-md-6">
											<div class="form-group m-b-40" id="PK_HELP_SUB_CATEGORY_LABEL" >
												<div id="PK_HELP_SUB_CATEGORY_DIV">
													<label for="PK_HELP_SUB_CATEGORY">Subcategory</label>
													<span class="bar"></span>
													<select class="form-control" id="PK_HELP_SUB_CATEGORY" name="PK_HELP_SUB_CATEGORY" required="required">
														<option value="" >Select Subcategory</option>
														<? $res_dd = $db->Execute("select PK_HELP_SUB_CATEGORY,HELP_SUB_CATEGORY FROM DOA_M_HELP_SUB_CATEGORY WHERE ACTIVE = 1 AND PK_HELP_CATEGORY = '$PK_HELP_CATEGORY' ORDER BY HELP_SUB_CATEGORY ASC");
														while (!$res_dd->EOF) { ?>
															<option value="<?=$res_dd->fields['PK_HELP_SUB_CATEGORY']?>" <? if($res_dd->fields['PK_HELP_SUB_CATEGORY'] == $PK_HELP_SUB_CATEGORY ) echo "selected"; ?> ><?=$res_dd->fields['HELP_SUB_CATEGORY'] ?></option>
														<?	$res_dd->MoveNext();
														} ?>
													</select>
												</div>
											</div>
										</div>
									</div>

									<div class="row">
                                        <div class="col-md-6">
											<div class="form-group m-b-40">
												<label for="NAME_ENG">Title (English)</label>
												<span class="bar"></span>
												<input type="text" class="form-control required-entry" id="NAME_ENG" name="NAME_ENG" value="<?=$NAME_ENG?>" >
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group m-b-40">
												<label for="NAME_SPA">Title (Spanish)</label>
												<span class="bar"></span>
												<input type="text" class="form-control" id="NAME_SPA" name="NAME_SPA" value="<?=$NAME_SPA?>" >
											</div>
										</div>
									</div>

									<div class="row">
                                        <div class="col-md-6">
											<div class="form-group m-b-40">
												<label for="TOOL_CONTENT_ENG">Tooltip Help Text (English)</label>
												<span class="bar"></span>
												<textarea class="form-control required-entry" rows="2" id="TOOL_CONTENT_ENG" name="TOOL_CONTENT_ENG"><?=$TOOL_CONTENT_ENG?></textarea>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group m-b-40">
												<label for="TOOL_CONTENT_SPA">Tooltip Help Text (Spanish)</label>
												<span class="bar"></span>
												<textarea class="form-control" rows="2" id="TOOL_CONTENT_SPA" name="TOOL_CONTENT_SPA"><?=$TOOL_CONTENT_SPA?></textarea>
											</div>
										</div>
									</div>

									<div class="row">
                                        <div class="col-md-3">
											<div class="form-group m-b-40">
												<label for="DISPLAY_ORDER">Display Order</label>
												<span class="bar"></span>
												<input type="text" class="form-control" id="DISPLAY_ORDER" name="DISPLAY_ORDER" value="<?=$DISPLAY_ORDER?>" >
											</div>
										</div>

										 <div class="col-md-3">
											<? if($_GET['id'] != ''){ ?>
											<div class="form-group m-b-40">
												<div class="row form-group">
													<div class="custom-control col-md-3">Active</div>
													<div class="custom-control custom-radio col-md-3">
														<input type="radio" id="customRadio11" name="ACTIVE" value="1" <? if($ACTIVE == 1) echo "checked"; ?> class="custom-control-input">
														<label class="custom-control-label" for="customRadio11">Yes</label>
													</div>
													<div class="custom-control custom-radio col-md-3">
														<input type="radio" id="customRadio22" name="ACTIVE" value="0" <? if($ACTIVE == 0) echo "checked"; ?>  class="custom-control-input">
														<label class="custom-control-label" for="customRadio22">No</label>
													</div>
												</div>
											</div>
											<? } ?>
										</div>

										<div class="col-md-6">
											<div class="form-group m-b-40">
												<label for="URL">Tool Tip Location</label>
												<span class="bar"></span>
												<input type="text" class="form-control" id="URL" name="URL" value="<?=$URL?>" >
											</div>
										</div>
									</div>

									<div class="row">
                                        <div class="col-md-6">
											<div class="row">
												<div class="col-md-12">
													Help Text (English)
												</div>
												<div class="col-md-12">
													<textarea class="form-control required-entry rich" rows="2" id="CONTENT_ENG" name="CONTENT_ENG"><?=$CONTENT_ENG?></textarea>
												</div>
											</div>
										</div>
										<div class="col-md-6">
											<div class="row">
												<div class="col-md-12">
													Help Text (Spanish)
												</div>
												<div class="col-md-12">
													<textarea class="form-control rich" rows="2" id="CONTENT_SPA" name="CONTENT_SPA"><?=$CONTENT_SPA?></textarea>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-6">
											<div class="row">
												<div class="col-md-12">
													<a href="javascript:void(0)" onclick="add_attachment()" ><b>Add Attachments</b></a>
													<div id="attachments_div"> </div>
												</div>
											</div>
											<? if($_GET['id'] != ''){
												$res_type = $db->Execute("select PK_HELP_FILES,FILE_NAME,FILE_LOCATION from DOA_Z_HELP_FILES WHERE ACTIVE = 1 AND PK_HELP = '$_GET[id]' ");
												while (!$res_type->EOF) { ?>
													<div class="row">
														<div class="col-md-10">
															<a href="<?=$res_type->fields['FILE_LOCATION']?>" target="_blank" ><?=$res_type->fields['FILE_NAME']?></a>
														</div>
														<div class="col-md-2">
															<a href="javascript:void(0);" onclick="delete_row('<?=$res_type->fields['PK_HELP_FILES']?>','document')" title="Delete" class="btn"><i class="icon-trash"></i></a>
														</div>
													</div>
												<?	$res_type->MoveNext();
												}
											} ?>
										</div>
                                    </div>

									<div class="row">
                                        <div class="col-md-12">
											<div class="form-group m-b-5"  style="text-align:center;" >
												<br />
												<button type="submit" class="btn waves-effect waves-light btn-info">Submit</button>

												<button type="button" class="btn waves-effect waves-light" onclick="window.location.href='manage_help.php'" >Cancel</button>

											</div>
										</div>
									</div>
                                </form>
                            </div>
                        </div>
					</div>
				</div>

            </div>
        </div>

		<div class="modal" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Confirmation.</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">�</button>
                    </div>
                    <div class="modal-body">
                            <p>Are you sure want to Delete this Image?</p>
							<input type="hidden" id="DELETE_ID" value="0" />
							<input type="hidden" id="DELETE_TYPE" value="0" />
                    </div>
                    <div class="modal-footer">
						<button type="button" onclick="conf_delete(1)" class="btn waves-effect waves-light btn-info">Yes</button>
							<button type="button" class="btn waves-effect waves-light btn-dark" onclick="conf_delete(0)" >No</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
		<?php require_once('../includes/footer.php');?>

		<link href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" rel="stylesheet" />
		<script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js"></script>
		<script src="https://cdn.tiny.cloud/1/d6quzxl18kigwmmr6z03zgk3w47922rw1epwafi19cfnj00i/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
	<script type="text/javascript">
		//var form1 = new Validation('form1');
		function delete_row(id,type){
			jQuery(document).ready(function($) {

				$("#deleteModal").show()
				$("#DELETE_ID").val(id)
				$("#DELETE_TYPE").val(type)
			});
		}

		function add_attachment(){
			var name  =  'ATTACHMENT[]';
			var data  =  '<div class="row" >';
				data += 	'<div class="col-lg-8">';
				data += 	 	'<input type="file" name="'+name+'" multiple />';
				data += 	 '</div>';
				data += '</div>';
			jQuery(document).ready(function($) {
				$("#attachments_div").append(data);
			});
		}

		function conf_delete(val,id){
			jQuery(document).ready(function($) {
				if(val == 1) {
					if($("#DELETE_TYPE").val() == 'document')
						window.location.href = 'help.php?act=delImg&id=<?=$_GET['id']?>&iid='+$("#DELETE_ID").val();

				} else
					$("#deleteModal").hide();
			});
		}

		jQuery(document).ready(function($) {
			tinymce.init({
				selector:'.rich',
				browser_spellcheck: true,
				menubar: 'file edit view insert format tools table tc help',
				statusbar: false,
				height: '300',
				plugins: [
					'advlist lists hr pagebreak',
					'wordcount code',
					'nonbreaking save table contextmenu directionality',
					'template paste textcolor colorpicker textpattern '
				],
				toolbar1: 'bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | forecolor backcolor',
				paste_data_images: true,
				height: 400,
			});
		});
		function get_sub_category(val){
			jQuery(document).ready(function($) {
				var data  = 'cat='+val;
				var value = $.ajax({
					url: "ajax_get_help_sub_category.php",
					type: "POST",
					data: data,
					async: false,
					cache: false,
					success: function (data) {
						//alert(data)
						document.getElementById('PK_HELP_SUB_CATEGORY_DIV').innerHTML = data;
						document.getElementById('PK_HELP_SUB_CATEGORY_LABEL').classList.add("focused");

					}
				}).responseText;
			});
		}

	</script>

</body>

</html>
