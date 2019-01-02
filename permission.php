<?
require('system.php');
require('html.php');

enforce_permission('PERMISSIONS');

if (isset($_GET['log_id'])) {
	$res = db_single_row("SELECT * FROM log JOIN log_permissions ON log_permissions.log_permissions_id = foreign_id WHERE log_id = ? AND foreign_table = 'log_permissions'", $_GET['log_id']);
	if (!$res) fatal("log_permissions entry with log_id = {$_GET['log_id']} not found");
	//print_r($res);
} else {
	$res = array('auth_user' => '', 'permission' => '');
}

html_start(); ?>
<form action="do_permission.php?session_guid=<? echo($session_guid); ?>" method="POST" accept-charset="UTF-8">
<input type="text" name="auth_user" value="<?=$res['auth_user']?>">
<input type="text" name="permission" value="<?=$res['permission']?>">
<input type="submit" name="submit" value="opslaan">
<? if (isset($_GET['log_id'])) { ?>
<input type="hidden" name="log_id" value="<?=$_GET['log_id']?>">
<input type="submit" name="submit" value="verwijder">
<? } ?>
</form>
<? html_end();
?>
