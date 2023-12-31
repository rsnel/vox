<?php
require('system.php');
header('Content-type: text/plain');
print_r($GLOBALS['session_state']);
print_r($_GET);
echo("useragent_id=$useragent_id\n");
echo("session_config_id=$session_config_id\n");


// generate new guid
$session_guid = generate_session_guid();

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

db_exec('INSERT INTO session_log SET session_id = ?', $session_id);
$GLOBALS['session_state']['session_log_id'] =  mysqli_insert_id($GLOBALS['db']);

// set new session_id
$old_session_id = $GLOBALS['session_state']['session_id'];

$GLOBALS['session_state']['session_id'] = $session_id;

// get redirect location
$redirect_to = db_single_field(<<<EOQ
SELECT request_uri FROM session_log WHERE session_id = ? AND session_prev_log_id = ?
EOQ
	, $old_session_id, $_GET['session_log_id']);

//echo("redirect_to=$redirect_to\n");
// replace session_guid by new value
$really_redirect_to = preg_replace('/\?session_guid='.$_GET['session_guid'].'/', '?session_guid='.$session_guid, $redirect_to);
//echo("really redirect_to=$really_redirect_to\n"); 

header('Location: '.$really_redirect_to);


?>
