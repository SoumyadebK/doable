<?error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
require_once("../global/config.php");
if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' ){
	header("location:../index.php");
	exit;
}
if(!empty($_POST)){
		

	$RECEPTIONS 		 = $_POST['RECEPTION'];
	$FILE_NAMES 	 	 = $_POST['FILE_NAME'];
	$FILE_LOCATIONS 	 = $_POST['FILE_LOCATION'];
	$PK_EMAIL_ATTACHMENT = $_POST['PK_EMAIL_ATTACHMENT'];
	unset($_POST['RECEPTION']);
	unset($_POST['FILE_NAME']);
	unset($_POST['FILE_LOCATION']);
	unset($_POST['PK_EMAIL_ATTACHMENT']);

	if($_POST['REMINDER_DATE'] != '')
		$_POST['REMINDER_DATE'] = date("Y-m-d",strtotime($_POST['REMINDER_DATE']));

	if($_POST['DUE_DATE'] != '')
		$_POST['DUE_DATE'] = date("Y-m-d",strtotime($_POST['DUE_DATE']));

	$EMAIL = $_POST;
	if($_GET['id'] == '' || $_GET['type'] == 'forward'){
		$EMAIL['PK_EMAIL_STATUS']  	= 1;
		$EMAIL['CREATED_BY']  		= $_SESSION['PK_USER'];
		$EMAIL['CREATED_ON']  		= date("Y-m-d H:i");
		$EMAIL['INTERNAL_ID']  		= 0;
		db_perform('DOA_EMAIL', $EMAIL, 'insert');
		$PK_EMAIL = $db->insert_ID();

		$EMAIL1['INTERNAL_ID'] 	= $PK_EMAIL;
		$INTERNAL_ID			= $PK_EMAIL;
		db_perform('DOA_EMAIL', $EMAIL1, 'update'," PK_EMAIL = '$PK_EMAIL' ");
	} else {
		if($_GET['type'] == 'draft') {
			$PK_EMAIL = $_GET['id'];
			db_perform('DOA_EMAIL', $EMAIL, 'update'," PK_EMAIL = '1' AND CREATED_BY = '$_SESSION[PK_USER]' ");
		} else {
			$PK_EMAIL = $_GET['pk'];

			$res = $db->Execute("SELECT INTERNAL_ID from DOA_EMAIL WHERE PK_EMAIL = '$PK_EMAIL' ");
			$INTERNAL_ID = $res->fields['INTERNAL_ID'];

			$EMAIL['INTERNAL_ID'] 		= $INTERNAL_ID;
			$EMAIL['PK_EMAIL_STATUS']  	= 1;
			$EMAIL['CREATED_BY']  		= $_SESSION['PK_USER'];
			$EMAIL['CREATED_ON']  		= date("Y-m-d H:i");

			db_perform('DOA_EMAIL', $EMAIL, 'insert');
			$PK_EMAIL = $db->insert_ID();
		}

	}
	if(!empty($RECEPTIONS)){
		foreach($RECEPTIONS as $RECEPTION){
			$res = $db->Execute("select PK_EMAIL_RECEPTION from DOA_EMAIL_RECEPTION WHERE PK_EMAIL = '$PK_EMAIL' AND PK_USER = '$RECEPTION' ");

			if($res->RecordCount() == 0){
				$EMAIL_RECEPTION['INTERNAL_ID'] = $INTERNAL_ID;
				$EMAIL_RECEPTION['PK_EMAIL'] 	= $PK_EMAIL;
				$EMAIL_RECEPTION['PK_USER'] 	= $RECEPTION;
				$EMAIL_RECEPTION['VIWED'] 		= 0;
				$EMAIL_RECEPTION['REPLY'] 		= 0;
				$EMAIL_RECEPTION['DELETED'] 	= 0;
				$EMAIL_RECEPTION['CREATED_ON']  = date("Y-m-d H:i");
				db_perform('DOA_EMAIL_RECEPTION', $EMAIL_RECEPTION, 'insert');
				$PK_EMAIL_RECEPTION_IDS[] =  $db->insert_ID();
			} else {
				$PK_EMAIL_RECEPTION_IDS[] = $res->fields['PK_EMAIL_RECEPTION'];
			}
		}
	}

	$cond = "";
	if(!empty($PK_EMAIL_RECEPTION_IDS)){
		$cond = " AND PK_EMAIL_RECEPTION NOT IN (".implode(",",$PK_EMAIL_RECEPTION_IDS).") ";
	}
	$db->Execute("DELETE from DOA_EMAIL_RECEPTION WHERE PK_EMAIL = '$PK_EMAIL' $cond ");

	$i = 0;
	if(!empty($FILE_NAMES)){
		foreach($FILE_NAMES as $FILE_NAME){
			$EMAIL_ATTACHMENT['PK_EMAIL'] 	 = $PK_EMAIL;
			$EMAIL_ATTACHMENT['FILE_NAME'] 	 = $FILE_NAME;
			$EMAIL_ATTACHMENT['LOCATION'] 	 = $FILE_LOCATIONS[$i];
			$EMAIL_ATTACHMENT['UPLOADED_ON'] = date("Y-m-d H:i");
			//if($PK_EMAIL_ATTACHMENT[$i] == '' || $_GET['type'] == 'reply'){
				db_perform('DOA_EMAIL_ATTACHMENT', $EMAIL_ATTACHMENT, 'insert');
			//}
			$i++;
		}
	}
	if($_GET['mail_type'] != ''){ ?>
		<script type="text/javascript" >
			window.opener.close_mail_window(this);
		</script>
	<? } else {
		if($_POST['DRAFT'] == 0)
			header("location:email.php");
		else
			header("location:email.php?type=draft");
	}
}

if($_GET['id'] == ''){
	$PK_EMAIL_TYPE 	= '';
	$SUBJECT 		= '';
	$CONTENT 		= '';
	$REMINDER_DATE 	= '';
	$DUE_DATE		= '';

} else {
	$table = "";
	if($_GET['type'] == 'reply' || $_GET['type'] == 'forward') {
		$cond  = " AND DOA_EMAIL_RECEPTION.PK_USER = '$_SESSION[PK_USER]' AND DOA_EMAIL_RECEPTION.PK_EMAIL = '$_GET[pk]' AND DOA_EMAIL.PK_EMAIL = DOA_EMAIL_RECEPTION.PK_EMAIL  ";
		$table = ",DOA_EMAIL_RECEPTION";
	} else
		$cond = " AND DOA_EMAIL.PK_EMAIL = '$_GET[id]' AND CREATED_BY = '$_SESSION[PK_USER]' ";

	$res = $db->Execute("select DOA_EMAIL.* from DOA_EMAIL $table WHERE 1=1 $cond");
	if($res->RecordCount() == 0 ){
		header("location:email.php?type=draft");
		exit;
	}

	$PK_EMAIL_TYPE 	= $res->fields['PK_EMAIL_TYPE'];
	$SUBJECT 		= $res->fields['SUBJECT'];

	if($_GET['type'] != 'reply') {
		$CONTENT 		= $res->fields['CONTENT'];
		$REMINDER_DATE 	= $res->fields['REMINDER_DATE'];
		$DUE_DATE		= $res->fields['DUE_DATE'];
	}

	if($REMINDER_DATE == '0000-00-00' || $REMINDER_DATE == '')
		$REMINDER_DATE = '';
	else
		$REMINDER_DATE = date("m/d/Y",strtotime($REMINDER_DATE));

	if($DUE_DATE == '0000-00-00' || $DUE_DATE == '')
		$DUE_DATE = '';
	else
		$DUE_DATE = date("m/d/Y",strtotime($DUE_DATE));
}
    $_GET['mail_type'] = 'quote';
if($_GET['mail_type'] != ''){
	if($_GET['mail_type'] == 'quote') {

		$PK_EMAIL_TYPE 	= '2';
		$res = $db->Execute("select QUOTE_NO from QUOTE_MASTER WHERE PK_QUOTE_MASTER = '$_GET[e_id]' ");
		$SUBJECT = 'Quote # '.$res->fields['QUOTE_NO'].' ';
	} else if($_GET['mail_type'] == 'order') {
		$PK_EMAIL_TYPE = '3';
		$res = $db->Execute("select ORDER_NO from ORDER_MASTER WHERE PK_ORDER_MASTER = '$_GET[e_id]' ");
		$SUBJECT = 'Order # '.$res->fields['ORDER_NO'].' ';
	} else if($_GET['mail_type'] == 'shipping') {
		$PK_EMAIL_TYPE = '5';
		$res_sm = $db->Execute("SELECT SHIPPING_MASTER.*,ORDER_NO, ORDER_MASTER.PK_ORDER_MASTER from ORDER_MASTER,SHIPPING_MASTER WHERE SHIPPING_MASTER.PK_SHIPPING_MASTER = '$_GET[e_id]' AND ORDER_MASTER.PK_ORDER_MASTER = SHIPPING_MASTER.PK_ORDER_MASTER ");
		$PK_ORDER_MASTER = $res_sm->fields['PK_ORDER_MASTER'];
		$SHIPPING_NO	 = $res_sm->fields['SHIPPING_NO'];
		$ORDER_NO	 	 = $res_sm->fields['ORDER_NO'];
		$SUBJECT = 'Shipping # '.$SHIPPING_NO.' (Order # '.$ORDER_NO.') ';
	} else if($_GET['mail_type'] == 'Order Payment' || $_GET['mail_type'] == 'Invoice Payment') {
		$PK_EMAIL_TYPE = '6';
		$SUBJECT = $_GET['mail_type'].' Ref # '.$_GET['e_id'];
	}
}

$email_show_type = $_GET['type'];
$user_id =  $_SESSION['PK_USER'];
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<body class="skin-default-dark fixed-layout">
<?php //require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content">

	<? if($_GET['mail_type'] == ''){
		require_once("menu.php");
	} else {
		echo "<br />";
	} ?>
	<div class="main" >
	  <div class="main-inner">
	    <div class="container">
	      <div class="row">
	        <div class="span2 col-md-2">
	          <div class="widget widget-nopad">
	            <!-- <div class="widget-header"> <i class="icon-list-alt"></i>
	              <h3> Internal Mail</h3>
	            </div> -->

	            <div class="widget-content">
	              <div class="widget big-stats-container">
	                <? require_once("menu_left_menu.php"); ?>
	              </div>
	            </div>
	          </div>
	        </div>
	        <div class="span10 col-md-10">
	        	<div class="card">
                    <div class="card-body">
			          <form method="post" action="email.php">
			            <div class="widget">
			              <div class="widget-header" >
			                <div class="span2" >&nbsp;</div>
			                <div class="span1" >
			                  <? if($_GET['type'] != 'sent' && $_GET['type'] != 'trash' && $_GET['type'] != 'draft' ) { ?>
			                  <!-- <button class="btn btn-danger" type="button" onclick="delete_row(2)" >Delete</button> -->
			                  <? } ?>
			                </div>
			              </div>
			              <div class="widget-content" style="padding-top: 10px">

			              	<?php
								if($email_show_type == ''){
									$res_type = $db->Execute("
																SELECT DOA_EMAIL.*, DOA_EMAIL_RECEPTION.VIWED FROM DOA_EMAIL_RECEPTION INNER JOIN DOA_EMAIL 
																ON DOA_EMAIL.PK_EMAIL = DOA_EMAIL_RECEPTION.PK_EMAIL WHERE PK_USER = $user_id AND DRAFT = 0 AND DOA_EMAIL.ACTIVE = 1 AND DOA_EMAIL_RECEPTION.DELETED=0
															");
								}elseif($email_show_type == 'sent'){
									$res_type = $db->Execute("SELECT * FROM DOA_EMAIL WHERE CREATED_BY = $user_id AND DRAFT = 0 AND ACTIVE = 1");
								}
								elseif($email_show_type == 'draft'){
									$res_type = $db->Execute("SELECT * FROM DOA_EMAIL WHERE CREATED_BY = $user_id AND DRAFT = 1 AND ACTIVE = 1");
								}
								elseif($email_show_type == 'starred'){
									$res_type = $db->Execute("
																SELECT DOA_EMAIL.* FROM DOA_EMAIL_STARRED INNER JOIN DOA_EMAIL 
																ON DOA_EMAIL.PK_EMAIL = DOA_EMAIL_STARRED.INTERNAL_ID WHERE PK_USER = $user_id AND DOA_EMAIL_STARRED.STARRED = 1 AND DOA_EMAIL.ACTIVE = 1
															");
								}
								elseif($email_show_type == 'trash'){
									$res_type = $db->Execute("SELECT * FROM DOA_EMAIL WHERE CREATED_BY = $user_id AND DRAFT = 1 AND ACTIVE = 0");
								}
			              	?>
			                
			                <table id="myTable" class="table table-striped border">
			                    <thead>
				                    <tr>
				                   	  <th>SL</th> 	
				                      <th>Subject</th>
				                      <th>Message</th>
				                      <th>Date Time</th>
				                      <th class="text-center">Action</th>
				                    </tr>
				                </thead>
				                <tbody>
				                <?php $sl = 1; ?> 	
								<?php while (!$res_type->EOF) : ?>					
									    <tr <?= ($res_type->fields['VIWED'] == 0) ? 'style="font-weight: 500;"':"" ;?>>
									      <td onclick="viewpage(<?=$res_type->fields['PK_EMAIL'];?>, '<?=$_GET['type']?>');"><?php echo $sl++; ?></td> 	
					                      <td onclick="viewpage(<?=$res_type->fields['PK_EMAIL'];?>, '<?=$_GET['type']?>');"><?php echo $res_type->fields['SUBJECT']; ?></td>
					                      <td onclick="viewpage(<?=$res_type->fields['PK_EMAIL'];?>, '<?=$_GET['type']?>');"><?php echo $res_type->fields['CONTENT']; ?></td>
					                      <td onclick="viewpage(<?=$res_type->fields['PK_EMAIL'];?>, '<?=$_GET['type']?>');"><?php echo date("Y-m-d H:i", strtotime($res_type->fields['CREATED_ON'])); ?></td>
					                      <td class="text-center">
					                      	<a href="view_email.php?id=<?=$res_type->fields['PK_EMAIL']?>&type=<?=$_GET['type']?>" style="color: #03a9f3;"><i class="ti-eye"></i></a>&nbsp;&nbsp;
					                      </td>
					                    </tr>
								<?php $res_type->MoveNext(); endwhile; ?>
			                  </tbody>
			                </table>
			              </div>
			            </div>
			          </form>
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
	<style>
	    .progress-bar {
	        border-radius: 5px;
	        height:18px !important;
	    }
	</style>
	<?php require_once('../includes/footer.php');?>

	 	<script>
            $(function () {
                $('#myTable').DataTable();
            });

            function viewpage(id, type=null){
		        window.location.href = "view_email.php?id="+id+"&type="+type;
		    }
        </script>

	</body>
	</body>
	</html>
