<?
require('system.php');
require('html.php');

enforce_staff_rights();

html_start(); ?>
<form action="do_su.php?session_guid=<? echo($session_guid); ?>" method="POST" accept-charset="UTF-8">
<br>Switch user: <input type="text" name="username">
<br> <input type="submit" value="Switch user">
</form>
<? html_end();
?>
