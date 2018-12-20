<?
require('system.php');
//header('Content-type: text/plain');
//print_r($GLOBALS['session_state']);
//print_r($_POST);

// true == accepted, false == rejected, null == error
function ext_check_basic($username, $password) {
	global $auth;

	if (!($ch = curl_init($auth['url']))) {
		warning('error initializing cURL');
		return NULL;
	}

	if (!curl_setopt($ch, CURLOPT_USERPWD, $auth['prefix'].$username.':'.$password)) goto error;

	if (!curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) goto error;

	if (!curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true)) goto error;
 
	if (!curl_setopt($ch, CURLOPT_TIMEOUT, 5)) goto error;

	if (!curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false)) goto error;

	if (!curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false)) goto error;

	if (curl_exec($ch) === false) goto error;

	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	// server returns 200 on 'access granted'
	// server returns 401 on 'access denied'
	// in case of something else (server error), the
	// server hopefully returns something else...
	if ($status == 200) return true;
	else if ($status == 401) return false;
	return NULL;

error:
	warning_curl($ch);
	return NULL;
}

// true == accepted, false == rejected, null == error
function ext_check_form($username, $password) {
	global $auth;

	if (!($ch = curl_init($auth['url'].'?username='.urlencode($auth['prefix'].$username).'&password='.urlencode($password)))) {
		warning('error initializing cURL');
		return NULL;
	}

	if (!curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) goto error;

	if (!curl_setopt($ch, CURLOPT_TIMEOUT, 5)) goto error;

	if (!curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false)) goto error;

	if (!curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false)) goto error;

	if (($verdict = curl_exec($ch)) === false) goto error;

	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	if ($verdict == 'access granted') return true;
	else if ($verdict == 'access denied') return false;

	return NULL;

error:
	warning_curl($ch);
	return NULL;
}

function ext_check_local($username, $password) {
	$password_hash = db_single_field(<<<EOQ
SELECT password_hash FROM log_passwords
JOIN log ON log.foreign_id = log_password_id
LEFT JOIN log AS log_next ON log_next.prev_log_id = log.log_id
WHERE log_next.log_id IS NULL
AND log_passwords.auth_user = ?
EOQ
		, $_POST['username']);
	if (!$password_hash || !hash_equals($password_hash, crypt($_POST['password'], $password_hash))) return false;
	return true;
}

if (!check_username($_POST['username'])) {
	$GLOBALS['session_state']['error_msg'] = 'Gebruikersnaam bevat niet-toegestane tekens.';
	goto exitlabel;
}

if ($GLOBALS['session_state']['auth_user']) {
	$GLOBALS['session_state']['error_msg'] = 'Deze sessie is reeds ingelogd.';
	goto exitlabel;
}

switch ($auth['method']) {
case 'Basic':
	$res = ext_check_basic($_POST['username'], $_POST['password']);
	break;
case 'Form':
	$res = ext_check_form($_POST['username'], $_POST['password']);
	break;
case 'Local':
	$res = ext_check_local($_POST['username'], $_POST['password']);
	break;
default:
	fatal('unknown auth method specified in config file: '.$auth['method']);
}

if ($res === true) {
	$GLOBALS['session_state']['auth_user'] = htmlenc($_POST['username']);
	$password_hash = db_single_field("SELECT password_hash FROM log_passwords JOIN log ON log.foreign_id = log_password_id LEFT JOIN log AS log_next ON log_next.prev_log_id = log.log_id WHERE log_next.log_id IS NULL AND log_passwords.auth_user = ?", $_POST['username']);
	if ($auth['method'] != 'Local' && !ext_check_local($_POST['username'], $_POST['password'])) 
		upsert_password($_POST['username'], $_POST['password']);
} else if ($res === false) {
	$GLOBALS['session_state']['error_msg'] = 'Ongeldige combinatie van gebruikersnaam en wachtwoord.';
} else if ($res === null) {
	$GLOBALS['session_state']['error_msg'] = 'Inloggen niet mogelijk door storing in authenticatieserver.';
}

exitlabel:

if (!preg_match('/\?session_guid=/', $GLOBALS['session_state']['request_uri']))
	$GLOBALS['session_state']['request_uri'] .= '?session_guid='.$GLOBALS['session_guid'];

header('Location: '.$GLOBALS['session_state']['request_uri']);

?>
