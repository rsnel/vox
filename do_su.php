<?
require('system.php');
enforce_staff_rights();

header('Content-type: text/plain');

print_r($_POST);

if (!checksetarray($_POST, array('username')))
	fatal('required fields are not set in $_POST');

$username = htmlenc($_POST['username']);

$ppl = db_single_row("SELECT ppl_id, ppl_type, ppl_login, ppl_active FROM $voxdb.ppl WHERE ppl_login = ?", $username);


if (!is_array($ppl)) {
	$GLOBALS['session_state']['error_msg'] = 'gebruiker '.$username.' onbekend, vraag admin hem of haar toe te voegen';
	header('Location: index.php?session_guid='.$session_guid);
	exit;
}
echo("new type={$ppl['ppl_type']}");

if ($ppl['ppl_type'] == 'personeel' && $ppl['ppl_login'] != $GLOBALS['session_state']['auth_user'] && !check_permission('SUPERSONEEL')) {
	$GLOBALS['session_state']['error_msg'] = 'U bent onbevoegd om de identiteit van een collega aan te nemen';
	header('Location: index.php?session_guid='.$session_guid);
	exit;
}

if (!$ppl['ppl_active']) {
	$GLOBALS['session_state']['error_msg'] = 'Gebruiker waarvan u de indentiteit wilt overnemen is gedeactiveerd door de beheerder.';
	header('Location: index.php?session_guid='.$session_guid);
	exit;
}

if ($GLOBALS['session_state']['ppl_id'] == $ppl['ppl_id']) {
	$GLOBALS['session_state']['error_msg'] = 'Je kunt geen SU doen naar de huidige effectieve gebruiker.';
	header('Location: index.php?session_guid='.$session_guid);
	exit;
}

$GLOBALS['session_state']['ppl_id'] = $ppl['ppl_id'];
if ($ppl['ppl_login'] == $GLOBALS['session_state']['auth_user']) {
	$GLOBALS['session_state']['success_msg'] = "su opgeheven";
} else $GLOBALS['session_state']['success_msg'] = "switched user to $username";

header('Location: index.php?session_guid='.$session_guid);

?>
