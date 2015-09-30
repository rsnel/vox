<?

function html_start() { ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Test</title>
</head>
<body>
<ul>
<li>session_log_id=<? echo($GLOBALS['session_state']['session_log_id']) ?></li>
<li>session_id=<? echo($GLOBALS['session_id']) ?></li>
<li>auth_user=<? echo(($GLOBALS['session_state']['auth_user'])?$GLOBALS['session_state']['auth_user']:'<i>NULL</i>'); ?></li>
<li>ppl_id=<? echo(($GLOBALS['session_state']['ppl_id'])?$GLOBALS['session_state']['ppl_id']:'<i>NULL</i>'); ?></li>
</ul>
<ul>
<? if ($GLOBALS['session_state']['auth_user']) { ?>
<li><form method="POST" action="do_logout.php?session_guid=<? echo($GLOBALS['session_guid']); ?>"><input type="submit" value="logout"></form></li>
<? } else { ?>
<li><form method="POST" action="do_login.php?session_guid=<? echo($GLOBALS['session_guid']); ?>"><input type="text" placeholder="gebruikersnaam" name="username"><input type="password" placeholder="wachtwoord" name="password"><input type="submit" value="login"></form></li>
<? } ?>
<li><form method="POST" action="do_new_tab.php?session_guid=<? echo($GLOBALS['session_guid']); ?>&amp;session_log_id=<? echo($GLOBALS['session_state']['session_log_id']); ?>" target="_blank"><input type="submit" value="new tab"></form></li>
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
</body>
</html>
<?  }
?>
