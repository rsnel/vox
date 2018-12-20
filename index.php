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

html_end();
?>
