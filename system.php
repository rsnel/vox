<?  

// the default config file is config.php, it can be overridden
// in the Apache2 config like so:
//
// SetEnv LOGDB_CONFIG 'alt'
//
// in the case the file config_alt.php will be loaded

if (isset($_SERVER['LOGDB_CONFIG'])) {
	$session_config = $_SERVER['LOGDB_CONFIG'];
	$configfile = 'config_'.$_SERVER['LOGDB_CONFIG'].'.php';
} else {
	$session_config = '';
	$configfile = 'config.php';
}

require($configfile);

// fatal() is for system errors that should not happen during usage
function fatal($string) {
	header('Content-type: text/plain');
	error_log('session_log_prev_id='.$GLOBALS['session_state']['session_log_id'].':fatal:'.$string);
	echo("fatal:$string");
	exit;
}

function warning($string) {
	error_log('session_log_prev_id='.$GLOBALS['session_state']['session_log_id'].':warning:'.$string);
}

function checksetarray($base, $keys) {
	foreach ($keys as $key) {
		if (!isset($base[$key])) return false;
	}

	return true;
}

if (!checksetarray($auth, array('url', 'prefix', 'method')))
	fatal('$auth array not defined or complete '.
			'(server, prefix) in '.$configfile);

require('db.php');

function fatal_curl($ch) {
	fatal('(errno='.curl_errno($ch).'):'.curl_error($ch));
}

function warning_curl($ch) {
	warning('(errno='.curl_errno($ch).'):'.curl_error($ch));
}

// session management
$useragent_id = db_get_useragent_id($_SERVER['HTTP_USER_AGENT']);
$session_config_id = db_get_id('session_config_id', 'session_configs', 'session_config', $session_config);

function generate_session_guid() {
	$guid = bin2hex(openssl_random_pseudo_bytes(32, $strong));
	if (!$strong) fatal('no strong pseudorandom generator available to generate session_guid');
	return $guid;
}

if ($_SERVER['QUERY_STRING'] != '' && !preg_match('/^session_guid=[0-9a-f]{64}(&.*)?$/', $_SERVER['QUERY_STRING'])) 
	fatal('impossible query string detected');

// a session is defined by the GUID of the client, the IP address of the client and the UserAgent of the client
if (!isset($_GET['session_guid']) ||
	!($session_id = db_single_field(<<<EOQ
SELECT session_id FROM sessions
WHERE session_guid = ?
AND session_useragent_id = ?
AND session_address = ?
AND session_config_id = ?
EOQ
, $_GET['session_guid'], $useragent_id, $_SERVER['REMOTE_ADDR'], $session_config_id))) {
	if (!isset($_GET['session_guid']) || (isset($_GET['session_guid']) && !preg_match('/^[0-9a-f]{64}$/', $_GET['session_guid']))) {
		$session_guid = generate_session_guid();
	} else {
		$session_guid = $_GET['session_guid'];
	}

	if (db_exec(<<<EOQ
INSERT INTO sessions
SET session_guid = ?,
	session_useragent_id = ?,
	session_address = ?,
	session_config_id = ?
EOQ
, $session_guid, $useragent_id, $_SERVER['REMOTE_ADDR'], $session_config_id) != 1)
		fatal('unable to insert new session in DB');
	$session_id =  mysqli_insert_id($GLOBALS['db']);

	// this is a new session, make a starting record session_log
	db_exec('INSERT INTO session_log SET session_id = ?', $session_id);
} else {
	if (!preg_match('/^[0-9a-f]{64}$/', $_GET['session_guid']))
		fatal('impossible: illegal session_guid in database?!?!?!');
	$session_guid = $_GET['session_guid'];
}

$GLOBALS['session_state'] = db_single_row(<<<EOQ
SELECT session_log.session_log_id, session_log.session_id, session_log.auth_user,
	session_log.ppl_id, TIMESTAMPDIFF(SECOND, session_log.timestamp, NOW()) age,
	session_log.success_msg, session_log.error_msg, session_log.request_uri FROM session_log
LEFT JOIN session_log AS session_log_next ON session_log_next.session_prev_log_id = session_log.session_log_id
WHERE session_log.session_id = ? AND session_log_next.session_prev_log_id IS NULL
EOQ
, $session_id);

if (!$GLOBALS['session_state']) fatal('impossible, session not found in session_log');

function write_state() {
	db_exec(<<<EOQ
INSERT INTO session_log
SET session_prev_log_id = ?, session_id = ?, auth_user = ?, ppl_id = ?,
	request_uri = ?, success_msg = ?, error_msg = ?
EOQ
	, 
		$GLOBALS['session_state']['session_log_id'],
		$GLOBALS['session_state']['session_id'],
		$GLOBALS['session_state']['auth_user'],
		$GLOBALS['session_state']['ppl_id'],
		$_SERVER['REQUEST_URI'],
		$GLOBALS['session_state']['success_msg'],
		$GLOBALS['session_state']['error_msg']);
}

// write session state at shutdown
register_shutdown_function('write_state');

?>
