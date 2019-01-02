<?
require('system.php');
require('html.php');

enforce_permission("CONFIGS");

$res = db_query(<<<EOQ
SELECT *, CONCAT('<a href="edit_config.php?session_guid=$session_guid&amp;log_id=', log_id, '">[edit]</a>') edit
FROM config
EOQ
);

html_start(); ?>
<a href="edit_config.php?session_guid=<?=$session_guid?>">insert</a>
<? db_dump_result($res, true);
html_end();
?>
