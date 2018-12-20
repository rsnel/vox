<?
require('system.php');
require('html.php');

enforce_logged_in();

$res = db_query(<<<EOQ
SELECT log.log_id, session_log.auth_user, timestamp, log_permissions.auth_user, permission,
CONCAT('<a href="permission.php?session_guid=$session_guid&amp;log_id=', log.log_id, '">[edit]</a>') edit
FROM log
JOIN log_permissions ON log_permissions.log_permissions_id = log.foreign_id
JOIN session_log USING (session_prev_log_id)
LEFT JOIN log AS log_next ON log_next.prev_log_id = log.log_id
WHERE log.foreign_table = 'log_permissions' AND log_next.log_id IS NULL
EOQ
);

html_start(); ?>
<a href="permission.php?session_guid=<?=$session_guid?>">insert</a>
<? db_dump_result($res, true);
html_end();
?>
