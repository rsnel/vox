<?
require('system.php');
require('html.php');

enforce_permission('CONFIGS');

if (isset($_GET['log_id'])) {
	$res = db_single_row("SELECT * FROM log JOIN log_config ON log_config.log_config_id = foreign_id WHERE log_id = ? AND foreign_table = 'log_config'", $_GET['log_id']);
	if (!$res) fatal("log_config entry with log_id = {$_GET['log_id']} not found");
	//print_r($res);
} else {
	$res = array('config_key' => '', 'config_value' => '');
}

html_start(); ?>
<form action="do_config.php?session_guid=<? echo($session_guid); ?>" method="POST" accept-charset="UTF-8">
<input type="text" name="config_key" value="<?=$res['config_key']?>">
<input type="text" name="config_value" value="<?=$res['config_value']?>">
<input type="submit" name="submit" value="opslaan">
<? if (isset($_GET['log_id'])) { ?>
<input type="hidden" name="log_id" value="<?=$_GET['log_id']?>">
<input type="submit" name="submit" value="verwijder">
<? } ?>
</form>
<? html_end();
?>
