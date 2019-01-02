<?
require('system.php');
enforce_logged_in();

header('Content-type: text/plain');

print_r($_POST);

if (!checksetarray($_POST, array('old_password', 'new_password', 'new_password2')))
	fatal('required fields are not set in $_POST');

$username = db_single_field("SELECT ppl_login FROM $voxdb.ppl WHERE ppl_id = ?", $GLOBALS['session_state']['ppl_id']);

$password_hash = db_single_field(<<<EOQ
SELECT password_hash FROM log_passwords
JOIN log ON log.foreign_id = log_password_id
LEFT JOIN log AS log_next ON log_next.prev_log_id = log.log_id
WHERE log_next.log_id IS NULL
AND log_passwords.auth_user = ?
EOQ
, $username);

if (!$password_hash || !hash_equals($password_hash, crypt($_POST['old_password'], $password_hash))) {
	$GLOBALS['session_state']['error_msg'] = 'onjuist wachtwoord ingevuld bij "huidig wachtwoord"';
	header('Location: edit_password.php?session_guid='.$session_guid);
	exit;
}

if ($_POST['new_password'] !== $_POST['new_password2']) {
	$GLOBALS['session_state']['error_msg'] = 'ingevulde nieuwe wachtwoorden zijn niet gelijk';
	header('Location: edit_password.php?session_guid='.$session_guid);
        exit;
}

if ($_POST['new_password'] === '') {
	$GLOBALS['session_state']['error_msg'] = 'nieuw wachtwoord mag niet leeg zijn';
	header('Location: edit_password.php?session_guid='.$session_guid);
	exit;
}

upsert_password($username, $_POST['new_password']);

$GLOBALS['session_state']['success_msg'] = "Wachtwoord aangepast";
header('Location: index.php?session_guid='.$session_guid);

/*
if ($auth_user == '') {
	$GLOBALS['session_state']['error_msg'] = 'auth_user mag niet leeg zijn';
	header('Location: permissions.php?session_guid='.$session_guid);
	exit;
}

if (preg_match('/[^A-Za-z0-9.]/', $auth_user)) {
        $GLOBALS['session_state']['error_msg'] = 'auth_user bevat niet-toegestane tekens.';
	header('Location: permissions.php?session_guid='.$session_guid);
	exit;
}

if (!preg_match('/[A-Z]+/', $permission)) {
        $GLOBALS['session_state']['error_msg'] = 'permission bevat niet-toegestane tekens.';
	header('Location: permissions.php?session_guid='.$session_guid);
	exit;
}

$log_permissions_id = db_get_id('log_permissions_id', 'log_permissions', 'auth_user', $auth_user,
	'permission', $permission, 'session_config_id', $session_config_id);

switch ($_POST['submit']) {
case 'opslaan':
	db_direct("LOCK TABLES log WRITE, log AS log_next READ");

	$log_id = db_single_field(<<<EOQ
SELECT log.log_id
FROM log
LEFT JOIN log AS log_next
ON log_next.prev_log_id = log.log_id
WHERE log_next.log_id IS NULL
AND log.foreign_table = 'log_permissions'
AND log.foreign_id = ?
EOQ
		, $log_permissions_id);
	if ($log_id) {
		$GLOBALS['session_state']['error_msg'] = $auth_user.' heeft al permissie '.$permission;
	} else {
		db_exec("INSERT INTO log ( prev_log_id, foreign_table, foreign_id, session_prev_log_id ) VALUES ( ?, 'log_permissions', ?, ? )", NULL, $log_permissions_id, $GLOBALS['session_state']['session_log_id']);
		$GLOBALS['session_state']['success_msg'] = $auth_user.' heeft permissie '.$permission.' gekregen van '.$GLOBALS['session_state']['auth_user'];
	}
	db_direct("UNLOCK TABLES");

	break;
case 'verwijder':
	db_direct("LOCK TABLES log WRITE, log AS log_next READ");
	$prev_log_id = db_single_field("SELECT prev_log_id FROM log AS log_next WHERE prev_log_id = ?", $_POST['log_id']);
	if ($prev_log_id) {
		$GLOBALS['session_state']['error_msg'] = "versie die gewist moest worden is al aangepast/verwijderd";
	} else {
		db_exec("INSERT INTO log ( prev_log_id, foreign_table, foreign_id, session_prev_log_id ) VALUES ( ?, 'log_permissions', NULL, ?)", $_POST['log_id'], $GLOBALS['session_state']['session_log_id']);
		$GLOBALS['session_state']['success_msg'] = $GLOBALS['session_state']['auth_user'].' heeft permissie ingetrokken';
	}
	db_direct("UNLOCK TABLES");
	break;
default:
	fatal('invalid value of submit');
}

header('Location: permissions.php?session_guid='.$session_guid);
 */

?>
