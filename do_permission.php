<?
require('system.php');
enforce_permission('PERMISSIONS');

header('Content-type: text/plain');

print_r($_POST);

if (!checksetarray($_POST, array('auth_user', 'permission', 'submit')))
	fatal('required fields are not set in $_POST');

$auth_user = htmlenc(trim($_POST['auth_user']));
$permission = htmlenc(trim($_POST['permission']));

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


?>
