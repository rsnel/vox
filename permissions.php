<?
require('system.php');
require('html.php');

enforce_permission("PERMISSIONS");

$res = db_query(<<<EOQ
SELECT *, CONCAT('<a href="permission.php?session_guid=$session_guid&amp;log_id=', log_id, '">[edit]</a>') edit
FROM permissions
EOQ
);

html_start(); ?>
<a href="permission.php?session_guid=<?=$session_guid?>">insert</a>
<? db_dump_result($res, true);
html_end();
?>
