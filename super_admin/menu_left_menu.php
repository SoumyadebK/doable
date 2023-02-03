
<style>
	.mail_active{color:red}
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
		<div class=""> <i class="icon-list-alt"></i>
			<h5 style="font-weight:bold;">Doable Connect</h5>
		</div>
		<div class="widget-content" style="padding-top: 10px">
			<div class="mail_left_menu">
				<button class="btn btn-info" onclick="window.location.href='compose.php'" >Compose</button>
			</div>
			<div class="mail_left_menu">
				<a href="email.php" <? if($_GET['type'] == '') { ?> class="mail_active" <? } ?>  >Inbox (<?=$count_inbox->fields['totalrows'];?>)</a>
			</div>
			<div class="mail_left_menu">
				<a href="email.php?type=starred" <? if($_GET['type'] == 'starred') { ?> class="mail_active" <? } ?> >Starred</a>
			</div>
			<div class="mail_left_menu">
				<a href="email.php?type=sent" <? if($_GET['type'] == 'sent') { ?> class="mail_active" <? } ?> >Sent</a>
			</div>
			<div class="mail_left_menu">
				<a href="email.php?type=draft" <? if($_GET['type'] == 'draft') { ?> class="mail_active" <? } ?> >Drafts</a>
			</div>
			<div class="mail_left_menu">
				<a href="email.php?type=trash" <? if($_GET['type'] == 'trash') { ?> class="mail_active" <? } ?> >Trash</a>
			</div>
		</div>
	</div>
</div>
