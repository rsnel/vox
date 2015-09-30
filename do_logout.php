<?
require('system.php');

$GLOBALS['session_state']['auth_user'] = NULL;
$GLOBALS['session_state']['ppl_id'] = NULL;

header('Location: '.$GLOBALS['session_state']['request_uri']);

?>
