<?
require('system.php');
require('html.php');

html_start();

if (check_permission('ACCOUNT')) { ?>
<a href="account.php?session_guid=<?=$session_guid?>">accountbeheer</a>
<? } 

if (check_permission('PERMISSIONS')) { ?>
<a href="permissions.php?session_guid=<?=$session_guid?>">permissionsbeheer</a>
<? } 

if (check_logged_in()) { ?>
<a href="edit_password.php?session_guid=<?=$session_guid?>">wachtwoord wijzigen</a>
<? }

if (check_staff()) { ?>
<a href="su.php?session_guid=<?=$session_guid?>">switch user</a>
<? }

html_end();

?>
