<?php

function html_start($script = '') {
	global $voxdb;

	?>
	<!DOCTYPE html>
	<html>
	<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/style.css">
	<!-- Toevoegen van verschillende icoontjes en resources -->
	<link rel="apple-touch-icon" sizes="120x120" href="/vox/images/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/vox/images/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/vox/images/favicon-16x16.png">
	<link rel="manifest" href="/vox/images/site.webmanifest">
	<link rel="mask-icon" href="/vox/images/safari-pinned-tab.svg" color="#5bbad5">
	<link rel="shortcut icon" href="/vox/images/favicon.ico">
	<script type="text/javascript" src="js/jquery-3.3.1.min.js"></script>
	<meta name="msapplication-TileColor" content="#da532c">
	<meta name="msapplication-config" content="/vox/images/browserconfig.xml">
	<meta name="theme-color" content="#ffffff">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>VOX inschrijfsysteem</title>
	</head>
	<body>
	<div class="flex-wrapper">
	<div class="container">
	<!-- Debuginformatie en menubalk -->
	<ul class="debug">
		<li id="session_log_id">session_log_id=<?php echo $GLOBALS['session_state']['session_log_id']; ?></li>
		<li id="session_id">session_id=<?php echo $GLOBALS['session_id']; ?></li>
		<li id="auth_user">auth_user=<?php echo ($GLOBALS['session_state']['auth_user']) ? $GLOBALS['session_state']['auth_user'] : '<i>NULL</i>'; ?></li>
		<li id="ppl_id">ppl_id=<?php echo ($GLOBALS['session_state']['ppl_id']) ? $GLOBALS['session_state']['ppl_id'] : '<i>NULL</i>'; ?></li>
		<?php
		if (check_su()) {
		?>
			<li>switched user naar 
				<?php
				echo db_single_field("SELECT ppl_login FROM $voxdb.ppl WHERE ppl_id = ?", $GLOBALS['session_state']['ppl_id']); 
				?>
			</li>
		<?php 
		} 
		?>
	</ul>
	<ul id="menu">
		<?php if (check_logged_in()) { ?>
			<li>
				<form method="POST" action="do_logout.php?session_guid=<?php echo $GLOBALS['session_guid']; ?>">
					ingelogd als <?php echo $GLOBALS['session_state']['auth_user']; ?> 
					<input type="submit" value="logout">
				</form>
			</li>
			<?php if (check_su()) { ?>
				<li>
					<form method="POST" action="do_su.php?session_guid=<?php echo $GLOBALS['session_guid']; ?>">
						suid <?php echo db_single_field("SELECT ppl_login FROM $voxdb.ppl WHERE ppl_id = ?", $GLOBALS['session_state']['ppl_id']); ?>
						<input type="hidden" name="username" value="<?php echo $GLOBALS['session_state']['auth_user']; ?>">
						<input type="submit" value="switch terug">
					</form>
				</li>
			<?php } ?>
		<?php } else if (check_staff()) { ?>
			<li>
				<form method="POST" action="do_su.php?session_guid=<?php echo $GLOBALS['session_guid']; ?>">
					<input type="text" name="username" value="">
					<input type="submit" value="switch user">
				</form>
			</li>
		<?php }
		if (!preg_match("/rooster.php/", $_SERVER['PHP_SELF']) && !preg_match("/klassenlijst.php/", $_SERVER['PHP_SELF'])) { ?>
			<li><a href="rooster.php?session_guid=<?php echo $GLOBALS['session_guid']; ?>">rooster</a></li>
		<?php }
		if (!preg_match("/edit_password.php/", $_SERVER['PHP_SELF'])) { ?>
			<li><a href="edit_password.php?session_guid=<?php echo $GLOBALS['session_guid']; ?>">wijzig ww.</a></li>
		<?php } ?>
	</ul>
	<?php if (!check_logged_in()) { ?>
		<li>
			<form method="POST" action="do_login.php?session_guid=<?php echo $GLOBALS['session_guid']; ?>">
				<input type="text" placeholder="gebruikersnaam" name="username" autofocus>
				<input type="password" placeholder="wachtwoord" name="password">
				<input type="submit" value="login">
			</form>
		</li>
	<?php } ?>

	<!--<li><form method="POST" action="do_new_tab.php?session_guid=<?php echo($GLOBALS['session_guid']); ?>&amp;session_log_id=<?php echo($GLOBALS['session_state']['session_log_id']); ?>" target="_blank"><input type="submit" value="new tab"></form></li>-->
	<?php if (check_staff_rights() && !preg_match("/niet_ingeschreven.php/", $_SERVER['PHP_SELF'])) { ?>
		<li><a href="niet_ingeschreven.php?session_guid=<?php echo $GLOBALS['session_guid']?>">niet ingeschreven</a></li>
	<?php } ?>
	<?php if (!preg_match("/index.php/", $_SERVER['PHP_SELF'])) { ?>
		<li><a href="index.php?session_guid=<?php echo $GLOBALS['session_guid']?>">home</a></li>
	<?php } ?>
	<?php if (check_permission('ACCOUNT') && !preg_match("/account.php/", $_SERVER['PHP_SELF'])) { ?>
		<li><a href="account.php?session_guid=<?php echo $GLOBALS['session_guid']?>">accounts</a></li>
	<?php } ?>
	<?php if (check_permission('PERMISSIONS') && !preg_match("/permissions?.php/", $_SERVER['PHP_SELF'])) { ?>
		<li><a href="permissions.php?session_guid=<?php echo $GLOBALS['session_guid']?>">pb</a></li>
	<?php } ?>
	<?php if (check_permission('CONFIGS') && !preg_match("/(configs.php|edit_config.php)/", $_SERVER['PHP_SELF'])) { ?>
		<li><a href="configs.php?session_guid=<?php echo $GLOBALS['session_guid']?>">cb</a></li>
	<?php } ?>
	<?php if (check_permission('TAGBEHEER') && !preg_match("/tags.php/", $_SERVER['PHP_SELF'])) { ?>
		<li><a href="tags.php?session_guid=<?php echo $GLOBALS['session_guid']?>">tags</a></li>
	<?php } ?>
	<?php if (check_permission('WEEKBEHEER') && !preg_match("/weken.php/", $_SERVER['PHP_SELF']) && !preg_match("/week_ops.php/", $_SERVER['PHP_SELF'])) { ?>
		<li><a href="weken.php?session_guid=<?php echo $GLOBALS['session_guid']?>">weken</a></li>
	<?php } ?>
	</ul>
	<?php if ($GLOBALS['session_state']['success_msg']) { ?>
	<div id="successmsg"><span class="textual">success:</span>
	<?php echo(($GLOBALS['session_state']['success_msg'])?$GLOBALS['session_state']['success_msg']:'<i>NULL</i>'); ?></div>
	<?php $GLOBALS['session_state']['success_msg'] = NULL;
	} ?>
	<?php if ($GLOBALS['session_state']['error_msg']) { ?>
	<div id="errormsg"><span class="textual">error:</span>
	<?php echo(($GLOBALS['session_state']['error_msg'])?$GLOBALS['session_state']['error_msg']:'<i>NULL</i>'); ?></div>
	<?php  $GLOBALS['session_state']['error_msg'] = NULL;
	} ?>
	<div style="clear: both"></div>
	</div>
	</div>
	<!-- Voegt de footer toe met informatie en links -->	
	<div id="footer">
	<div id="footerlogo">
	<img src="images/AGPLv3_Logo.svg">
	</div>
	<div id="footertext">
	VOX Inschrijfsysteem &copy; 2018-2019 Rik Snel &lt;rik@snel.it&gt;.<br>
	Released as <a href="http://www.gnu.org/philosophy/free-sw.html">free software</a> without warranties under <a href="http://www.fsf.org/licensing/licenses/agpl-3.0.html">GNU AGPL v3</a>.<br>
	Sourcecode: git clone <a href="https://github.com/rsnel/vox/">https://github.com/rsnel/vox/</a>
	</div>
	</div>
	</body>
	</html>
<?php  
}
?>
