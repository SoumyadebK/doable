<?error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
require_once("../global/config.php");
if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' ){
	header("location:../index.php");
	exit;
}


if (empty($_GET['id']))
    $title = "Help Category";
else
    $title = "Help Category";

if(!empty($_POST)){
	//echo "<pre>";print_r($_POST);exit;
	$HELP_CATEGORY = $_POST;
	if($_GET['id'] == ''){
		$HELP_CATEGORY['CREATED_BY']  = $_SESSION['PK_USER'];
		$HELP_CATEGORY['CREATED_ON']  = date("Y-m-d H:i");

		if(empty($_POST['DISPLAY_ORDER'])) unset($HELP_CATEGORY['DISPLAY_ORDER']);

		db_perform('DOA_M_HELP_CATEGORY', $HELP_CATEGORY, 'insert');
		$PK_HELP_CATEGORY = $db->insert_ID();
	} else {
		$HELP_CATEGORY['EDITED_BY'] = $_SESSION['PK_USER'];
		$HELP_CATEGORY['EDITED_ON'] = date("Y-m-d H:i");
		db_perform('DOA_M_HELP_CATEGORY', $HELP_CATEGORY, 'update'," PK_HELP_CATEGORY = '$_GET[id]'");
		$PK_HELP_CATEGORY = $_GET['id'];
	}
	header("location:manage_help_category.php");
}

//$_GET['id'] ='';
if($_GET['id'] == ''){
	$HELP_CATEGORY 	  = '';
	$DISPLAY_ORDER	  = '';

} else {
	$res = $db->Execute("SELECT * FROM DOA_M_HELP_CATEGORY WHERE PK_HELP_CATEGORY = '$_GET[id]' ");
	if($res->RecordCount() == 0){
		header("location:manage_help_category.php");
		exit;
	}

	$HELP_CATEGORY 	 	  	= $res->fields['HELP_CATEGORY'];
	$ACTIVE  	 	  		= $res->fields['ACTIVE'];
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
        <div class="container-fluid">
                 <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><? if($_GET['id'] == '') echo "Add"; else echo "Edit"; ?> <?php echo $title; ?> </h4>
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
												<label for="HELP_CATEGORY">Help Category</label>
												<span class="bar"></span>
												<input type="text" class="form-control required-entry" id="HELP_CATEGORY" name="HELP_CATEGORY" value="<?=$HELP_CATEGORY?>" required>
											</div>
										</div>

										<div class="col-md-6">
											<div class="form-group m-b-40">
												<label for="DISPLAY_ORDER">Display Order</label>
												<span class="bar"></span>
												<input type="text" class="form-control" id="DISPLAY_ORDER" name="DISPLAY_ORDER" value="<?=$DISPLAY_ORDER?>" >
											</div>
										</div>
									</div>

									<div class="row">
                                        
										 <div class="col-md-6">
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

									</div>

									<div class="row">
                                        <div class="col-md-12">
											<div class="form-group m-b-5"  style="text-align:center;" >
												<br />
												<button type="submit" class="btn waves-effect waves-light btn-info">Submit</button>

												<button type="button" class="btn waves-effect waves-light" onclick="window.location.href='manage_help_category.php'" >Cancel</button>

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
