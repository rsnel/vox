<?
require('system.php');
enforce_permission('CONFIGS');

header('Content-type: text/plain');

print_r($_POST);

if (!checksetarray($_POST, array('config_key', 'config_value', 'submit')))
	fatal('required fields are not set in $_POST');

$config_key = htmlenc(trim($_POST['config_key']));
$config_value = htmlenc(trim($_POST['config_value']));

$log_config_id = db_get_id('log_config_id', 'log_config', 'config_key', $config_key,
	'config_value', $config_value, 'session_config_id', $session_config_id);

switch ($_POST['submit']) {
case 'opslaan':
	db_direct("LOCK TABLES log WRITE, log AS log_next READ");

	$log_id = db_single_field(<<<EOQ
SELECT log.log_id
FROM log
LEFT JOIN log AS log_next
ON log_next.prev_log_id = log.log_id
WHERE log_next.log_id IS NULL
AND log.foreign_table = 'log_config'
AND log.foreign_id = ?
EOQ
		, $log_config_id);

	if ($log_id) {
		$GLOBALS['session_state']['error_msg'] = "config bestaat al"; 
	} else {
		db_exec("INSERT INTO log ( prev_log_id, foreign_table, foreign_id, session_prev_log_id ) VALUES ( ?, 'log_config', ?, ? )", NULL, $log_config_id, $GLOBALS['session_state']['session_log_id']);
		$GLOBALS['session_state']['success_msg'] = 'config ingesteld';
	}
	db_direct("UNLOCK TABLES");

	break;
case 'verwijder':
	db_direct("LOCK TABLES log WRITE, log AS log_next READ");
	$prev_log_id = db_single_field("SELECT prev_log_id FROM log AS log_next WHERE prev_log_id = ?", $_POST['log_id']);
	if ($prev_log_id) {
		$GLOBALS['session_state']['error_msg'] = "versie die gewist moest worden is al aangepast/verwijderd";
	} else {
		db_exec("INSERT INTO log ( prev_log_id, foreign_table, foreign_id, session_prev_log_id ) VALUES ( ?, 'log_config', NULL, ?)", $_POST['log_id'], $GLOBALS['session_state']['session_log_id']);
		$GLOBALS['session_state']['success_msg'] = $GLOBALS['session_state']['auth_user'].' heeft config verwijderd';
	}
	db_direct("UNLOCK TABLES");
	break;
default:
	fatal('invalid value of submit');
}

header('Location: configs.php?session_guid='.$session_guid);


?>
