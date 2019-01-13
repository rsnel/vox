<?

function html_start() {
	global $voxdb;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="css/style.css">
<title>VOX inschrijfsysteem</title>
</head>
<body>
<ul class="debug">
<li id="session_log_id">session_log_id=<? echo($GLOBALS['session_state']['session_log_id']) ?></li>
<li id="session_id">session_id=<? echo($GLOBALS['session_id']) ?></li>
<li id="auth_user">auth_user=<? echo(($GLOBALS['session_state']['auth_user'])?$GLOBALS['session_state']['auth_user']:'<i>NULL</i>'); ?></li>
<li id="ppl_id">ppl_id=<? echo(($GLOBALS['session_state']['ppl_id'])?$GLOBALS['session_state']['ppl_id']:'<i>NULL</i>'); ?></li>
<? if (check_su()) { ?><li>switched user naar <?=db_single_field("SELECT ppl_login FROM $voxdb.ppl WHERE ppl_id = ?", $GLOBALS['session_state']['ppl_id']); ?></li><? } ?>
</ul>
<ul>
<? if (check_logged_in()) { ?>
<li><form method="POST" action="do_logout.php?session_guid=<? echo($GLOBALS['session_guid']); ?>">ingelogd als <?=$GLOBALS['session_state']['auth_user']?> <input type="submit" value="logout"></form></li>
<? if (check_su()) { ?><li><form method="POST" action="do_su.php?session_guid=<? echo($GLOBALS['session_guid']); ?>">su identiteit <?=db_single_field("SELECT ppl_login FROM $voxdb.ppl WHERE ppl_id = ?", $GLOBALS['session_state']['ppl_id'])?><input type="hidden" name="username" value="<?=$GLOBALS['session_state']['auth_user']?>"> <input type="submit" value="switch terug"></form></li>
<? } else if (check_staff()) { ?><li><form method="POST" action="do_su.php?session_guid=<? echo($GLOBALS['session_guid']); ?>"><input type="text" name="username" value=""><input type="submit" value="switch user"></form>
</li><? }
if (!preg_match("/edit_password.php/", $_SERVER['PHP_SELF'])) {
?>
<li><a href="edit_password.php?session_guid=<?=$GLOBALS['session_guid']?>">wachtwoord wijzigen</a></li>
<? } } else { ?>
<li><form method="POST" action="do_login.php?session_guid=<? echo($GLOBALS['session_guid']); ?>"><input type="text" placeholder="gebruikersnaam" name="username"><input type="password" placeholder="wachtwoord" name="password"><input type="submit" value="login"></form></li>
<? } ?>
<!--<li><form method="POST" action="do_new_tab.php?session_guid=<? echo($GLOBALS['session_guid']); ?>&amp;session_log_id=<? echo($GLOBALS['session_state']['session_log_id']); ?>" target="_blank"><input type="submit" value="new tab"></form></li>-->
<? if (!preg_match("/rooster.php/", $_SERVER['PHP_SELF']) && !preg_match("/klassenlijst.php/", $_SERVER['PHP_SELF'])) { ?>
<li><a href="rooster.php?session_guid=<?=$GLOBALS['session_guid']?>">rooster</a></li>
<? } ?>
<? if (check_staff_rights() && !preg_match("/niet_ingeschreven.php/", $_SERVER['PHP_SELF'])) { ?>
<li><a href="niet_ingeschreven.php?session_guid=<?=$GLOBALS['session_guid']?>">niet ingeschreven</a></li>
<? } ?>
<? if (!preg_match("/index.php/", $_SERVER['PHP_SELF'])) { ?>
<li><a href="index.php?session_guid=<?=$GLOBALS['session_guid']?>">home</a></li>
<? } ?>
<? if (check_permission('ACCOUNT') && !preg_match("/account.php/", $_SERVER['PHP_SELF'])) { ?>
<li><a href="account.php?session_guid=<?=$GLOBALS['session_guid']?>">accountbeheer</a></li>
<? } ?>
<? if (check_permission('PERMISSIONS') && !preg_match("/permissions?.php/", $_SERVER['PHP_SELF'])) { ?>
<li><a href="permissions.php?session_guid=<?=$GLOBALS['session_guid']?>">permissionsbeheer</a></li>
<? } ?>
<? if (check_permission('CONFIGS') && !preg_match("/(configs.php|edit_config.php)/", $_SERVER['PHP_SELF'])) { ?>
<li><a href="configs.php?session_guid=<?=$GLOBALS['session_guid']?>">configbeheer</a></li>
<? } ?>
<? if (check_permission('TAGBEHEER') && !preg_match("/tags.php/", $_SERVER['PHP_SELF'])) { ?>
<li><a href="tags.php?session_guid=<?=$GLOBALS['session_guid']?>">tagbeheer</a></li>
<? } ?>
<? if (check_permission('WEEKBEHEER') && !preg_match("/weken.php/", $_SERVER['PHP_SELF']) && !preg_match("/week_ops.php/", $_SERVER['PHP_SELF'])) { ?>
<li><a href="weken.php?session_guid=<?=$GLOBALS['session_guid']?>">weekbeheer</a></li>
<? } ?>
</ul>
<? if ($GLOBALS['session_state']['success_msg']) { ?>
<div id="errormsg"><span class="textual">success:</span>
<? echo(($GLOBALS['session_state']['success_msg'])?$GLOBALS['session_state']['success_msg']:'<i>NULL</i>'); ?></div>
<?	$GLOBALS['session_state']['success_msg'] = NULL;
} ?>
<? if ($GLOBALS['session_state']['error_msg']) { ?>
<div id="successmsg"><span class="textual">error:</span>
<? echo(($GLOBALS['session_state']['error_msg'])?$GLOBALS['session_state']['error_msg']:'<i>NULL</i>'); ?></div>
<?  $GLOBALS['session_state']['error_msg'] = NULL;
	}
}

function html_end() { ?>
<div id="footer"></div>
</body>
</html>
<?  }
?>
