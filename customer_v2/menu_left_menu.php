
<style>
	.mail_active{color:red}

    .mail_left_menu {
        padding: 10px;
    }
</style>
<?php
	$count_inbox = $db->Execute("
								SELECT  COUNT(DOA_EMAIL.PK_EMAIL) AS totalrows FROM DOA_EMAIL_RECEPTION 
								INNER JOIN DOA_EMAIL 
								ON DOA_EMAIL.PK_EMAIL = DOA_EMAIL_RECEPTION.PK_EMAIL 
								WHERE PK_USER = '$_SESSION[PK_USER]' AND VIWED = 0 AND DRAFT = 0 AND DOA_EMAIL.ACTIVE = 1 AND DOA_EMAIL_RECEPTION.DELETED=0");
?>
<div class="card">
	<div class="card-body">
		<div class="widget-header"> <i class="icon-list-alt"></i>
			<h5 style="font-weight:bold;">Doable Connect</h5>
		</div>
		<div class="widget-content" style="padding-top: 10px">
			<div class="mail_left_menu">
				<button class="btn btn-info" onclick="window.location.href='compose.php'" >Compose</button>
			</div>
			<div class="mail_left_menu">
				<a href="email.php" <?php if($_GET['type'] == '') { ?> class="mail_active" <?php } ?>  >Inbox (<?=$count_inbox->fields['totalrows'];?>)</a>
			</div>
			<div class="mail_left_menu">
				<a href="email.php?type=starred" <?php if($_GET['type'] == 'starred') { ?> class="mail_active" <?php } ?> >Starred</a>
			</div>
			<div class="mail_left_menu">
				<a href="email.php?type=sent" <?php if($_GET['type'] == 'sent') { ?> class="mail_active" <?php } ?> >Sent</a>
			</div>
			<div class="mail_left_menu">
				<a href="email.php?type=draft" <?php if($_GET['type'] == 'draft') { ?> class="mail_active" <?php } ?> >Drafts</a>
			</div>
			<div class="mail_left_menu">
				<a href="email.php?type=trash" <?php if($_GET['type'] == 'trash') { ?> class="mail_active" <?php } ?> >Trash</a>
			</div>
		</div>
	</div>
</div>
