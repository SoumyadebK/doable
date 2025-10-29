<style>
	/* Container */
	.mail_sidebar {
		background: #fff;
		border-radius: 12px;
		box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
		padding: 15px 0;
		font-family: 'Roboto', sans-serif;
		width: 215px;
	}

	/* Compose Button */
	.mail_compose_btn {
		display: block;
		border: 1px solid #39b54a;
		color: #39b54a;
		font-weight: 500;
		border-radius: 20px;
		text-align: center;
		padding: 10px 0;
		margin: 0 auto 15px auto;
		width: 80%;
		text-decoration: none;
		transition: all 0.2s ease;
	}

	.mail_compose_btn:hover {
		background: #e5f6e8ff;
	}

	.mail_compose_btn_active {
		background: #d9fcdeff;
		color: #39b54a;
		font-weight: 600;
	}

	/* Menu items */
	.mail_left_menu {
		padding: 5px 20px;
	}

	.mail_left_menu a {
		display: flex;
		align-items: center;
		gap: 12px;
		padding: 8px 10px;
		border-radius: 16px;
		text-decoration: none;
		color: #202124;
		font-size: 14px;
		font-weight: 500;
		transition: background 0.2s ease, color 0.2s ease;
	}

	.mail_left_menu a:hover {
		background: #e5f6e8ff;
	}

	.mail_left_menu a.mail_active {
		background: #d9fcdeff;
		color: #39b54a;
		font-weight: 600;
	}

	/* Badge (for inbox count) */
	.mail_count {
		background: #e8eaed;
		border-radius: 12px;
		padding: 2px 8px;
		font-size: 12px;
		margin-left: auto;
		color: #5f6368;
	}

	.mail_left_menu i {
		width: 18px;
		text-align: center;
		font-size: 15px;
	}

	/* Header */
	.mail_header {
		font-weight: bold;
		font-size: 16px;
		color: #202124;
		padding: 0 20px 10px 20px;
		border-bottom: 1px solid #f1f3f4;
		margin-bottom: 10px;
	}
</style>

<?php
$count_inbox = $db->Execute("SELECT COUNT(DOA_EMAIL.PK_EMAIL) AS totalrows 
                                FROM DOA_EMAIL_RECEPTION 
                                INNER JOIN DOA_EMAIL ON DOA_EMAIL.PK_EMAIL = DOA_EMAIL_RECEPTION.PK_EMAIL 
                                WHERE PK_USER = '$_SESSION[PK_USER]' 
                                AND VIWED = 0 
                                AND DRAFT = 0 
                                AND DOA_EMAIL.ACTIVE = 1 
                                AND DOA_EMAIL_RECEPTION.DELETED = 0");
$inbox_count = ($count_inbox->RecordCount() > 0) ? $count_inbox->fields['totalrows'] : 0;
?>

<div class="mail_sidebar">
	<div class="mail_header">Doable Connect</div>

	<a class="mail_compose_btn <?php if ($type == 'compose') echo 'mail_compose_btn_active'; ?>" href="compose.php?type=compose">
		<i class="fa fa-pencil-alt"></i> Compose
	</a>

	<div class="mail_left_menu">
		<a href="email.php?type=inbox" class="<?php if ($type == 'inbox') echo 'mail_active'; ?>">
			<i class="fa fa-inbox"></i> Inbox
			<span class="mail_count"><?= $inbox_count ?></span>
		</a>
	</div>

	<div class="mail_left_menu">
		<a href="email.php?type=starred" class="<?php if ($type == 'starred') echo 'mail_active'; ?>">
			<i class="fa fa-star"></i> Starred
		</a>
	</div>

	<div class="mail_left_menu">
		<a href="email.php?type=sent" class="<?php if ($type == 'sent') echo 'mail_active'; ?>">
			<i class="fa fa-paper-plane"></i> Sent
		</a>
	</div>

	<div class="mail_left_menu">
		<a href="email.php?type=draft" class="<?php if ($type == 'draft') echo 'mail_active'; ?>">
			<i class="fa fa-file"></i> Drafts
		</a>
	</div>

	<div class="mail_left_menu">
		<a href="email.php?type=trash" class="<?php if ($type == 'trash') echo 'mail_active'; ?>">
			<i class="fa fa-trash"></i> Trash
		</a>
	</div>
</div>