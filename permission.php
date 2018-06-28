<?
require('system.php');
require('html.php');

html_start(); ?>
<form action="do_permission.php?session_guid=<? echo($session_guid); ?>" method="POST" accept-charset="UTF-8">
<input type="text" name="auth_user">
<input type="text" name="permission">
<input type="submit" name="submit" value="opslaan">
</form>
<? html_end();
?>
