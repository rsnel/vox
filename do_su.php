<?
require('system.php');
enforce_staff();

header('Content-type: text/plain');

print_r($_POST);

if (!checksetarray($_POST, array('username')))
	fatal('required fields are not set in $_POST');

$username = htmlenc($_POST['username']);

$ppl = db_single_row("SELECT ppl_id, ppl_type FROM $voxdb.ppl WHERE ppl_login = ?", $username);


if (!is_array($ppl)) {
	$GLOBALS['session_state']['error_msg'] = 'gebruiker '.$username.' onbekend, vraag admin hem of haar toe te voegen';
	header('Location: su.php?session_guid='.$session_guid);
	exit;
}
echo("new type={$ppl['ppl_type']}");

$GLOBALS['session_state']['ppl_id'] = $ppl['ppl_id'];
$GLOBALS['session_state']['success_msg'] = "switched user to $username";

header('Location: index.php?session_guid='.$session_guid);

?>
