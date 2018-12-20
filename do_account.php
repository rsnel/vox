<?
require('system.php');
enforce_permission('ACCOUNT');

header('Content-type: text/plain');

print_r($_POST);

if (!checksetarray($_POST, array('username', 'password')))
	fatal('required fields are not set in $_POST');

upsert_password($_POST['username'], $_POST['password']);

header('Location: account.php?session_guid='.$session_guid);


?>
